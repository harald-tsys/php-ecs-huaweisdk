<?php

require_once __DIR__ . '/../vendor/autoload.php';

use OTC\fg_timer_event\TimerEvent;
use HuaweiCloud\SDK\Ecs\V2\Model\ServerId;
use HuaweiCloud\SDK\Core\Auth\BasicCredentials;
use HuaweiCloud\SDK\Core\Http\HttpConfig;
use HuaweiCloud\SDK\Core\Exceptions\ConnectionException;
use HuaweiCloud\SDK\Core\Exceptions\RequestTimeoutException;
use HuaweiCloud\SDK\Core\Exceptions\ServiceResponseException;
use HuaweiCloud\SDK\Ecs\V2\EcsClient;
use HuaweiCloud\SDK\Ecs\V2\Model\BatchStartServersRequest;
use HuaweiCloud\SDK\Ecs\V2\Model\BatchStartServersRequestBody;
use HuaweiCloud\SDK\Ecs\V2\Model\BatchStartServersOption;

function handler($event, $context)
{
  $logger = $context->getLogger();

  $timerEvent = new TimerEvent($event);
  $timerName = $timerEvent->getTriggerName();
  $userEvent = $timerEvent->getUserEvent();

  $instanceId = $context->getUserData('ECS_INSTANCE_ID');
  $logger->info("Timer $timerName received with user event: $userEvent for ECS instance: $instanceId");

  $accessKey = $context->getSecurityAccessKey();
  $secretKey = $context->getSecuritySecretKey();
  $securityToken = $context->getSecurityToken();

  // get project_id and instance_id from environment variables
  $projectId = getenv('RUNTIME_PROJECT_ID');

  // get ecs endpoint from context or use default
  $ecsEndpoint = $context->getUserData('ECS_ENDPOINT') ?: 'https://ecs.eu-de.otc.t-systems.com';


  // Regional services
  $basicCredentials = new BasicCredentials($accessKey, $secretKey, $projectId);
  $basicCredentials = $basicCredentials->withSecurityToken($securityToken);

  $config = HttpConfig::getDefaultConfig();
  $config->setIgnoreSslVerification(true);

  $client = EcsClient::newBuilder()
    ->withHttpConfig($config)
    ->withEndpoint($ecsEndpoint)
    ->withCredentials($basicCredentials)
    ->build();

  $request = new BatchStartServersRequest();


  $body = new BatchStartServersRequestBody();
  $listOsstartServers = array();
  array_push(
    $listOsstartServers,
    (new ServerId())
      ->setId($instanceId)
  );

  $osstartbody = new BatchStartServersOption();
  $osstartbody->setServers($listOsstartServers);
  $body->setOsstart($osstartbody);
  $request->setBody($body);


  try {
    $response = $client->BatchStartServers($request);
    echo "\n";
    echo $response;
  } catch (ConnectionException $e) {
    $msg = $e->getMessage();
    echo "\n" . $msg . "\n";
  } catch (RequestTimeoutException $e) {
    $msg = $e->getMessage();
    echo "\n" . $msg . "\n";
  } catch (ServiceResponseException $e) {
    echo "\n";
    echo $e->getHttpStatusCode() . "\n";
    echo $e->getRequestId() . "\n";
    echo $e->getErrorCode() . "\n";
    echo $e->getErrorMsg() . "\n";
  }


  return [
    'isBase64Encoded' => false,
    'headers' => ['Content-Type' => 'application/json'],
  ];
}