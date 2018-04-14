<?php

namespace lumenous\Repositories;

use lumenous\Repositories\Interfaces\CharityPayoutsRepositoryInterface;
use lumenous\Models\CharityPayout;

class EloquentCharityPayoutsRepository extends EloquentAbstractRepository implements CharityPayoutsRepositoryInterface {

    public function __construct()
    {
        $this->modelClass = 'lumenous\Models\CharityPayout';
    }

}
