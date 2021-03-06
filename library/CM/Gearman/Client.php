<?php

class CM_Gearman_Client {

    /** @var GearmanClient */
    private $_gearmanClient;

    /** @var CM_Jobdistribution_JobSerializer */
    private $_serializer;

    /**
     * @param GearmanClient                    $gearmanClient
     * @param CM_Jobdistribution_JobSerializer $serializer
     */
    public function __construct(GearmanClient $gearmanClient, CM_Jobdistribution_JobSerializer $serializer) {
        $this->_gearmanClient = $gearmanClient;
        $this->_serializer = $serializer;
    }

    /**
     * @param CM_Jobdistribution_Job_Abstract $job
     * @return mixed
     */
    public function run(CM_Jobdistribution_Job_Abstract $job) {
        $resultList = $this->runMultiple([$job]);
        return reset($resultList);
    }

    /**
     * @param CM_Jobdistribution_Job_Abstract[] $jobs
     * @return array
     * @throws CM_Exception
     */
    public function runMultiple(array $jobs) {
        $resultList = [];
        $this->_gearmanClient->setCompleteCallback(function (GearmanTask $task) use (&$resultList) {
            $resultList[] = $this->_serializer->unserializeJobResult($task->data());
        });

        \Functional\each($jobs, function (CM_Jobdistribution_Job_Abstract $job) {
            $workload = $this->_serializer->serializeJob($job);
            $task = $this->_gearmanClient->addTaskHigh($job->getJobName(), $workload);
            if (false === $task) {
                throw new CM_Exception('Cannot add task', null, ['jobName' => $job->getJobName()]);
            }
        });
        $this->_gearmanClient->runTasks();

        if (count($resultList) != count($jobs)) {
            throw new CM_Exception('Job failed. Invalid results', null, [
                'jobNameList'     => \Functional\map($jobs, function (CM_Jobdistribution_Job_Abstract $job) {
                    return $job->getJobName();
                }),
                'countResultList' => count($resultList),
                'countJobs'       => count($jobs),
            ]);
        }
        return $resultList;
    }

    /**
     * @param CM_Jobdistribution_Job_Abstract $job
     * @throws CM_Exception
     */
    public function queue(CM_Jobdistribution_Job_Abstract $job) {
        $gearmanClient = $this->_gearmanClient;

        $workload = $this->_serializer->serializeJob($job);
        $priority = $job->getPriority();
        switch ($priority) {
            case CM_Jobdistribution_Priority::HIGH:
                $gearmanClient->doHighBackground($job->getJobName(), $workload);
                break;
            case CM_Jobdistribution_Priority::NORMAL:
                $gearmanClient->doBackground($job->getJobName(), $workload);
                break;
            case CM_Jobdistribution_Priority::LOW:
                $gearmanClient->doLowBackground($job->getJobName(), $workload);
                break;
            default:
                throw new CM_Exception('Invalid priority', null, ['priority' => (string) $priority]);
        }
    }
}
