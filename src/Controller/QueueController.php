<?php

namespace Okvpn\Bundle\MQInsightBundle\Controller;

use Okvpn\Bundle\MQInsightBundle\Entity\MQStateStat;
use Okvpn\Bundle\MQInsightBundle\Manager\ProcessManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/queue-status")
 */
class QueueController extends Controller
{
    /**
     * @Template()
     * @Route("/", name="okvpn_mq_insight_status")
     * @AclAncestor("message_queue_view_stat")
     */
    public function statusAction()
    {
        $fetchFrom = new \DateTime();
        $fetchFrom->modify('-1 day');
        $data = $this->get('okvpn_mq_insight.chart_provider')->getQueueSizeData($fetchFrom);

        $provider = $this->get('okvpn_mq_insight.queued_messages_provider');
        $runningConsumers = ProcessManager::getPidsOfRunningProcess('oro:message-queue:consume');
        $runningConsumers = $provider->filterNotActivePids($runningConsumers);

        /** @var MQStateStat $size */
        $size = $this->get('doctrine')
            ->getRepository('OkvpnMQInsightBundle:MQStateStat')
            ->getLastValue();

        $dailyStat = $this->get('okvpn_mq_insight.chart_provider')->getDailyStat();

        return [
            'entity' => new MQStateStat(),
            'sizeData' => $data,
            'fetchFrom' => $fetchFrom->format('c'),
            'running' => $runningConsumers,
            'runningCount' => count($runningConsumers),
            'size' => $size ? $size->getQueue() : null,
            'dailyStat' => $dailyStat
        ];
    }

    /**
     * @Template()
     * @Route("/info", name="okvpn_mq_insight_plot")
     * @AclAncestor("message_queue_view_stat")
     */
    public function plotAction()
    {
        return [];
    }

    /**
     * @Template()
     * @Route("/pie-chart", name="okvpn_mq_insight_pie_chart")
     * @AclAncestor("message_queue_view_stat")
     */
    public function pieChartAction()
    {
        $fetchFrom = new \DateTime();
        $fetchFrom->modify('-1 day');

        $repository = $this->get('doctrine')->getRepository('OkvpnMQInsightBundle:ProcessorStat');
        $data = $repository->summaryStat($fetchFrom);

        $pieData = [];
        foreach ($data as $value) {
            $pieData[] = [$value['name'], $value['totalTime']];
        }

        return [
            'pieData' => $pieData
        ];
    }

    /**
     * @Route("/queued", name="okvpn_mq_insight_queued")
     * @AclAncestor("message_queue_view_stat")
     *
     * @param Request $request
     * @return Response
     */
    public function queuedAction(Request $request)
    {
        $provider = $this->get('okvpn_mq_insight.queued_messages_provider');
        $result = $provider->getQueuedMessages();

        $runningConsumers = $provider->filterNotActivePids(
            ProcessManager::getPidsOfRunningProcess('oro:message-queue:consume')
        );

        if ($request->get('isLast')) {
            $result = end($result) ?: [];
        }

        return new JsonResponse(
            [
                'runningConsumers' => $runningConsumers,
                'queued' => $result,
                'size' => $this->get('okvpn_mq_insight.queue_provider')->getApproxQueueCount()
            ]
        );
    }
}
