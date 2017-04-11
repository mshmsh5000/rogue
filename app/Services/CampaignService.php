<?php

namespace Rogue\Services;

use Rogue\Repositories\CacheRepository;

class CampaignService
{
    /**
     * Create new Registrar instance.
     */
    public function __construct()
    {
        $this->phoenix = new Phoenix;
        $this->cache = new CacheRepository;
    }

    /**
     * Finds a single campaign in Rogue. If the campaign is not found
     * in the Cache, then we grab it directly from Phoenix and store in cache.
     *
     * @param  string $id
     * @return object $user Northstar user object
     */
    public function find($id)
    {
        $campaign = $this->cache->retrieve($id);

        if (! $campaign) {
            $campaign = $this->phoenix->getCampaign($id);

            $this->cache->store($campaign['data']['id'], $campaign['data']);
        }

        return $campaign['data'];
    }

    /**
     * Finds a group of campagins in Rogue/Phoenix.
     *
     * @param  array $ids
     * @return collection $campaigns|null
     */
    public function findAll(array $ids = [])
    {
        if ($ids) {
            $campaigns = $this->cache->retrieveMany($ids);

            if (! $campaigns) {
                $campaigns = $this->getBatchedCollection($ids);

                if (count($campaigns)) {
                    $group = $campaigns->keyBy('id')->all();

                    $this->cache->storeMany($group);
                }
            } else {
                $campaigns = $this->resolveMissingCampaigns($campaigns);

                $campaigns = collect(array_values($campaigns));
            }

            return $campaigns;
        }

        return null;
    }

    /**
     * Resolving missing cached users in a user cache collection.
     *
     * @param  array $users
     * @return array
     */
    protected function resolveMissingCampaigns($campaigns)
    {
        foreach ($campaigns as $key => $value) {
            if ($value === false or $value === null) {
                $campaigns[$key] = $this->find($key);
            }
        }

        return $campaigns;
    }

    /**
     * Get large number of users in batches from Northstar.
     *
     * @param  array  $ids
     * @param  int $size
     * @return \Illuminate\Support\Collection
     */
    protected function getBatchedCollection($ids, $size = 50)
    {
        $count = intval(ceil(count($ids) / 50));
        $index = 0;
        $data = [];

        for ($i = 0; $i < $count; $i++) {
            $batch = array_slice($ids, $index, $size);

            $parameters['count'] = '5000';
            $parameters['campaigns'] = implode(',', $batch);

            $campaigns = $this->phoenix->getAllCampaigns($parameters);

            $data = array_merge($data, $campaigns['data']);

            $index += $size;
        }

        return collect($data);
    }

    /**
     * Group a collection of campaigns by cause space.
     *
     * @param  array  $ids
     * @param  int $size
     * @return \Illuminate\Support\Collection
     */
    public function groupByCause($campaigns)
    {
        $grouped = $campaigns->groupBy(function ($campaign) {
            if ($campaign['staff_pick']) {
                return 'Staff Pick';
            }

            if (! $campaign['causes']['primary']['name']) {
                return 'No Cause';
            }

            $cause = ucfirst($campaign['causes']['primary']['name']);

            return $cause;
        });

        $sorted = $grouped->sortBy(function ($campaigns, $key) {
            // Hacky solution to move the Staff Pick group to the top of the list,
            // the No Cause group to the end of the list,
            // and alphabetize everything inbetween.
            if ($key === 'Staff Pick') {
                return 'A';
            }

            if ($key === 'No Cause') {
                return 'Z';
            }

            return $key;
        });

        return $sorted->toArray();
    }
}
