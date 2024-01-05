<?php

namespace Ekolotech\MoMoGateway\Model;

enum GatewayProductTypeEnum
{
    case MtnCollectionGateway;
    case MtnDisbursementGateway;
    case AirtelGateway;
    case MyPVITGateway;
}