<?php

namespace Rogue\Http\Controllers\Api;

use Rogue\Models\Post;
use Illuminate\Http\Request;
use Rogue\Models\Reportback;
use Rogue\Services\ReportbackService;
use Rogue\Http\Requests\ReportbackRequest;
use Rogue\Http\Transformers\PhoenixGalleryTransformer;

class ReportbackController extends ApiController
{
    /**
     * @var \Rogue\Http\Transformers\ReportbackTransformer
     */
    protected $transformer;
    protected $itemTransformer;

    /**
     * Create new ReportbackController instance.
     */
    public function __construct(ReportbackService $reportbackService)
    {
        $this->reportbackService = $reportbackService;
        $this->transformer = new PhoenixGalleryTransformer;
    }

    /**
     * Returns Post data for the Phoenix-ashes gallery
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Create an empty Post query, which we can filter and paginate
        $query = $this->newQuery(Post::class);

        // 1. Join with signups so we can access the signup data and filter by campaign
        // 2. Only return approved Posts
        $query = $query->join('signups', 'signups.id', '=', 'posts.signup_id')
            ->where('status', '=', 'approved');

        // Only return Posts for the given campaign_id (if there is one)
        if (array_key_exists('campaign_id', $request->filter)) {
            $campaign_id = $request->filter['campaign_id'];

            $query = $query->where('campaign_id', '=', $campaign_id);
        }

        return $this->paginatedCollection($query, $request);
    }

    /**
     * Store a newly created resource in storage or
     * update a reportback if it already exists.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ReportbackRequest $request)
    {
        $transactionId = incrementTransactionId($request);

        // @TODO - instead should probably just have a method that gets northstar_id by default from a drupal_id if that is the only thing provided and then use that to find the reportback.
        $userId = $request['northstar_id'] ? $request['northstar_id'] : $request['drupal_id'];
        $type = $request['northstar_id'] ? 'northstar_id' : 'drupal_id';

        $reportback = $this->reportbackService->getReportback($request['campaign_id'], $request['campaign_run_id'], $userId, $type);

        $updating = ! is_null($reportback);

        if (! $updating) {
            $reportback = $this->reportbackService->create($request->all(), $transactionId);

            $code = 200;
        } else {
            $reportback = $this->reportbackService->update($reportback, $request->all(), $transactionId);

            $code = 201;
        }

        return $this->item($reportback, $code);
    }

    /**
     * Update reportbackitem(s) that already exists.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function updateReportbackItems(Request $request)
    {
        $this->validate($request, [
            '*.rogue_reportback_item_id' => 'required',
            '*.status' => 'required',
            '*.reviewer' => 'required',
        ]);

        $items = $this->reportbackService->updateReportbackItems($request->all());

        if (empty($items)) {
            $code = 404;
        } else {
            $code = 201;
        }

        $meta = [];

        return $this->collection($items, $code, $meta, $this->itemTransformer);
    }
}
