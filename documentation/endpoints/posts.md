## Posts

Create a Post and/or Create/Update a Signup

```
POST /api/v2/posts
```

  - **northstar_id**: (string) required.
    The northstar id of the user creating the post.
  - **campaign_id**: (int) required.
    The drupal node id of the campaign that the user's post is associated with. 
  - **campaign_run_id**: (int) required.
    The drupal campaign run node id of the campaign that the user's post is associated with.
  - **quantity**: (int).
    The number of reportback nouns verbed. 
  - **why_participated**: (string).
    The reason why the user participated.
  - **caption**: (string).
    Corresponding caption for the post.
  - **source**: (string).
    Where the post was submitted from.
  - **remote_addr**: (string).
    IP address of where the post is submitted from. 
  - **file**: (string) required.
    File string to save of post image.
  - **crop_x**: (int).
    The crop x coordinates of the post image if the user cropped the image.
  - **crop_y**: (int).
    The crop y coordinates of the post image if the user cropped the image.
  - **crop_width** (int).
    The copy width coordinates of the post image if the user cropped the image.
  - **crop_height** (int).
    The copy height coordinates of the post image if the user cropped the image.
  - **crop_rotate** (int).
    The copy rotate coordinates of the post image if the user cropped the image.

Example Response:

```
{
  "data": {
    "id": 340,
    "signup_id": 784,
    "northstar_id": "5571df46a59db12346dsb456d",
    "media": {
      "url": "https://s3.amazonaws.com/ds-rogue-prod/uploads/reportback-items/18-1487182498.jpeg",
      "caption": "Captioning captions",
    },
    "status": "pending",
    "remote_addr": "207.110.19.130",
    "post_source": "runscope",
    "created_at": "2017-02-15T18:14:58+0000",
    "updated_at": "2017-02-15T18:14:58+0000",
    "signup": {
      "signup_id": 4643,
      "northstar_id": "94958304d9w09293",
      "campaign_id": 1631,
      "campaign_run_id": 1903,
      "quantity": 500,
      "why_participated": "TEST123456",
      "signup_source": null,
      "created_at": "2017-03-29T13:37:16+00:00",
      "updated_at": "2017-07-11T21:23:16+00:00"
    },
  }
}
```

Allows admins to delete a post. Posts get soft deleted from the database.

```
DELETE posts/{post_id}
```

Example Response:

```
{
  "code": 200,
  "message": "Post deleted."
}
```
