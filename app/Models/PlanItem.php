<?php

namespace App\Models;


class PlanItem
{
    private $coachId;
    private  $planIds;

    /**
     * @return mixed
     */
    public function getCoachId()
    {
        return $this->coachId;
    }

    /**
     * @param mixed $coachId
     */
    public function setCoachId($coachId): void
    {
        $this->coachId = $coachId;
    }

    /**
     * @return mixed
     */
    public function getPlanIds()
    {
        return $this->planIds;
    }

    /**
     * @param mixed $planIds
     */
    public function setPlanIds($planIds): void
    {
        $this->planIds = $planIds;
    }


    /**
     * @return mixed
     * #todo add to web
     */
    public function all() {

        return Plan::select('plans.id as plan_id','price','title','type','user_id as coach_id')
            ->leftJoin('prices', 'prices.plan_id', '=', 'plans.id')
            ->whereIn('plans.id' , $this->getPlanIds())
            ->where('prices.user_id' , $this->getCoachId())
            ->get()->toArray();


    }


}
