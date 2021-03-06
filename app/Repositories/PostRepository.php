<?php

namespace Rogue\Repositories;

use Rogue\Models\Post;
use Rogue\Models\Event;
use Rogue\Services\AWS;
use Rogue\Models\Review;
use Rogue\Services\Registrar;
use Intervention\Image\Facades\Image;

class PostRepository
{
    /**
     * AWS service class instance.
     *
     * @var \Rogue\Services\AWS
     */
    protected $AWS;

    /**
     * Array of properties needed for cropping and rotating.
     *
     * @var array
     */
    protected $cropProperties = ['crop_x', 'crop_y', 'crop_width', 'crop_height', 'crop_rotate'];

    /**
     * Constructor
     */
    public function __construct(AWS $aws, Registrar $registrar)
    {
        $this->aws = $aws;
        $this->registrar = $registrar;
    }

    /**
     * Find a post by post_id and return associated signup and tags.
     *
     * @param int $id
     * @return \Rogue\Models\Post
     */
    public function find($id)
    {
        return Post::with('signup', 'tagged')->findOrFail($id);
    }

    /**
     * Create a Post.
     *
     * @param  array $data
     * @param  int $signupId
     * @return \Rogue\Models\Post|null
     */
    public function create(array $data, $signupId)
    {
        if (isset($data['file'])) {
            // Auto-orient the photo by default based on exif data.
            $image = Image::make($data['file'])->orientate();

            $fileUrl = $this->aws->storeImage((string) $image->encode('data-url'), $signupId);
        } else {
            $fileUrl = 'default';
        }

        // Create a post.
        $post = new Post([
            'signup_id' => $signupId,
            'northstar_id' => $data['northstar_id'],
            'url' => $fileUrl,
            'caption' => $data['caption'],
            'status' => isset($data['status']) ? $data['status'] : 'pending',
            'source' => $data['source'],
            'remote_addr' => $data['remote_addr'],
        ]);

        // @TODO: This can be removed after the migration
        // Let Laravel take care of the timestamps unless they are specified in the request
        if (isset($data['created_at'])) {
            $post->created_at = $data['created_at'];
            $post->updated_at = $data['created_at'];
            $post->save(['timestamps' => false]);

            $post->events->first()->created_at = $data['created_at'];
            $post->events->first()->updated_at = $data['created_at'];
            $post->events->first()->save(['timestamps' => false]);
        } else {
            $post->save();
        }

        // Edit the image if there is one
        if (isset($data['file'])) {
            $editedImage = $this->crop($data, $post->id);
        }

        return $post;
    }

    /**
     * Update an existing Post and Signup.
     *
     * @param \Rogue\Models\Post $signup
     * @param array $data
     *
     * @return \Rogue\Models\Post
     */
    public function update($signup, $data)
    {
        if (array_key_exists('updated_at', $data)) {
            $signup->fill(array_only($data, ['quantity', 'quantity_pending', 'why_participated', 'updated_at']));

            $signup->save(['timestamps' => false]);

            $event = $signup->events->last();
            $event->created_at = $data['updated_at'];
            $event->updated_at = $data['updated_at'];
            $event->save(['timestamps' => false]);
        } else {
            $signup->fill(array_only($data, ['quantity', 'quantity_pending', 'why_participated']));

            // Triggers model event that logs the updated signup in the events table.
            $signup->save();
        }

        // If there is a file, create a new post.
        if (array_key_exists('file', $data)) {
            return $this->create($data, $signup->id);
        }

        return $signup;
    }

    /**
     * Delete a post and remove the file from s3.
     *
     * @param int $postId
     * @return $post;
     */
    public function destroy($postId)
    {
        $post = Post::findOrFail($postId);

        // Delete the image file from AWS.
        $this->aws->deleteImage($post->url);

        // Set the url of the post to null.
        $post->url = null;
        $post->save();

        // Soft delete the post.
        $post->delete();

        return $post->trashed();
    }

    /**
     * Updates a post's status after being reviewed.
     *
     * @param array $data
     *
     * @return
     */
    public function reviews($data)
    {
        $post = Post::where(['id' => $data['post_id']])->first();

        // Create the Review.
        $review = Review::create([
            'signup_id' => $post->signup_id,
            'northstar_id' => $post->northstar_id,
            'admin_northstar_id' => $data['admin_northstar_id'],
            'status' => $data['status'],
            'old_status' => $post->status,
            'comment' => isset($data['comment']) ? $data['comment'] : null,
            'post_id' => $post->id,
        ]);

        // Update the status on the Post.
        $post->status = $data['status'];
        $post->save();

        return $post;
    }

    /**
     * Updates a post's tags when added or deleted.
     *
     * @param object $post
     * @param string $tag
     *
     * @return
     */
    public function tag($post, $tag)
    {
        // If the post already has the tag, soft delete. Otherwise, add the tag to the post.
        if (in_array($tag, $post->tagNames(), true)) {
            $post->untag($tag);
        } else {
            $post->tag($tag);
        }

        // Return the post object including the tags that are related to it.
        return Post::with('signup', 'tagged')->findOrFail($post->id);
    }

    /**
     * Crop an image
     *
     * @param  int $signupId
     * @return url|null
     */
    protected function crop($data, $postId)
    {
        $cropValues = array_only($data, $this->cropProperties);

        if (count($cropValues) > 0) {
            $editedImage = edit_image($data['file'], $cropValues);

            return $this->aws->storeImageData($editedImage, 'edited_' . $postId);
        } else {
            // Take center crop
            $editedImage = (string) Image::make($data['file'])
                        ->orientate()
                        ->fit(400)
                        ->encode('jpg', 75);

            return $this->aws->storeImageData($editedImage, 'edited_' . $postId);
        }

        return null;
    }
}
