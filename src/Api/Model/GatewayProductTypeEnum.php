<?php

namespace Ekolotech\MoMoGateway\Api\Model;

enum GatewayProductTypeEnum
{
    case MtnCollectionGateway;
    case MtnDisbursementGateway;
    case AirtelGateway;
    case MyPVITGateway;
}