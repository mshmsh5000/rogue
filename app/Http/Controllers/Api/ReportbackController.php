<?php

namespace Rogue\Http\Controllers\Api;

use Illuminate\Http\Request;
use Rogue\Models\Reportback;
use Rogue\Services\ReportbackService;
use Rogue\Http\Transformers\ReportbackTransformer;

class ReportbackController extends ApiController
{
    /**
     * @var \Rogue\Http\Transformers\ReportbackTransformer
     */
    protected $transformer;

    /**
     * Create new ReportbackController instance.
     */
    public function __construct(ReportbackService $reportbackService)
    {
        $this->reportbackService = $reportbackService;
        $this->transformer = new ReportbackTransformer;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $reportback = $this->reportbackService->create($request->all());

        return $this->item($reportback);
    }
}
