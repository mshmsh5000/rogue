<?php

namespace Rogue\Http\Transformers;

use Rogue\Models\Post;
use League\Fractal\TransformerAbstract;
use Rogue\Http\Transformers\SignupTransformer;

class PostTransformer extends TransformerAbstract
{
    /**
     * Transform resource data.
     *
     * @param \Rogue\Models\Post $post
     * @return array
     */
    public function transform(Post $post)
    {
        $signup = $post->signup;

        if (! is_null($signup->quantity_pending) && is_null($signup->quantity)) {
            $quantity = $signup->quantity_pending;
        } else {
            $quantity = $signup->quantity;
        }

        return [
            'id' => $post->id,
            'signup_id' => $post->signup_id,
            'northstar_id' => $post->northstar_id,
            'media' => [
                'url' => $post->url,
                'caption' => $post->caption,
            ],
            'tagged' => $post->tagNames(),
            'reactions' => $post->reactions,
            'status' => $post->status,
            'source' => $post->source,
            'remote_addr' => $post->remote_addr,
            'created_at' => $post->created_at->toIso8601String(),
            'updated_at' => $post->updated_at->toIso8601String(),
            'signup' => [
                'signup_id' => $signup->id,
                'northstar_id' => $signup->northstar_id,
                'campaign_id' => $signup->campaign_id,
                'campaign_run_id' => $signup->campaign_run_id,
                'quantity' => $quantity,
                'why_participated' => $signup->why_participated,
                'signup_source' => $signup->source,
                'created_at' => $signup->created_at->toIso8601String(),
                'updated_at' => $signup->updated_at->toIso8601String(),
            ],
        ];
    }
}
