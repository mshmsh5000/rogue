<?php

namespace Rogue\Http\Controllers\Api;

use Illuminate\Http\Request;
use Rogue\Services\SignupService;
use Rogue\Http\Transformers\SignupTransformer;
use Rogue\Repositories\PhotoRepository;

// use Rogue\Http\Requests;

class SignupsController extends ApiController
{
    /**
     * @var \League\Fractal\TransformerAbstract;
     */
    protected $transformer;

    /**
     * The signup service instance.
     *
     * @var Rogue\Services\SignupService
     */
    protected $signups;

    /**
     * The photo repository instance.
     *
     * @var Rogue\Repositories\PhotoRepository
     */
    protected $photo;

    /**
     * Create a controller instance.
     *
     * @param  PostContract  $posts
     * @return void
     */
    public function __construct(SignupService $signups, PhotoRepository $photo)
    {
        $this->signups = $signups;
        $this->photo = $photo;
    }

   	/**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $transactionId = incrementTransactionId($request);

        $signup = $this->signups->create($request->all(), $transactionId);

        if ($signup) {
        	$code = '200';
        }

        // get the data into the way we want to return it
        $this->transformer = new SignupTransformer;

        // @TODO: probably want to bust this out into it's own helper function
        // check to see if there is a reportback too
        if (array_key_exists('caption', $request->all())) {
			// create the photo and tie it to this signup
			$this->photo->create($request->all(), $signup->id);
        }

        return $this->item($signup, $code);


    }
}
