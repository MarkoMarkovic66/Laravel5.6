<?php
namespace App\Enums;

use App\Enums\BaseEnum;

/**
 * ApiErrorCodeType
 */
class ApiErrorCodeType extends BaseEnum {
    const API_ERROR_PARAMS_CODE     = '01';
    const API_ERROR_PARAMS_MSG      = 'api.error_params';

    const API_ERROR_LOGIN_CODE      = '02';
    const API_ERROR_LOGIN_MSG       = 'api.error_login';

    const API_ERROR_TOKEN_CODE      = '03';
    const API_ERROR_TOKEN_MSG       = 'api.error_token';

    const API_ERROR_VALIDATION_CODE = '04';
    const API_ERROR_VALIDATION_MSG  = 'api.error_validation';

    const API_ERROR_DB_CODE         = '05';
    const API_ERROR_DB_MSG          = 'api.error_db';

    const API_ERROR_LOGICAL_CODE    = '06';
    const API_ERROR_LOGICAL_MSG     = 'api.error_logical';

    const API_ERROR_OTHERS_CODE     = '07';
    const API_ERROR_OTHERS_MSG      = 'api.error_others';

    const API_ERROR_ACCOUNT_CODE     = '08';
    const API_ERROR_ACCOUNT_NOT_ACTIVE_MSG      = 'api.error_account_not_active';
    const API_ERROR_ACCOUNT_EXPIRED_MSG      = 'api.error_account_expired';


}
