<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 *  @author    Sirv Limited <support@sirv.com>
 *  @copyright Copyright (c) 2017 Sirv Limited. All rights reserved
 *  @license   https://www.magictoolbox.com/license/
 */

class SirvAPIClient
{
    private $clientId = '';
    private $clientSecret = '';
    private $clientId_default = 'CCvbv8cbDcgijrSOrLd4sQ80jiN';
    private $clientSecret_default = '02gC7DoQ/wyKUliskFeQnjaYIZtMEFzJu7/TH3ayyNahkKfd4Nmaxw871FikWeRG2W9KEKB0JOelKibQw6QbeA==';
    private $token = '';
    private $tokenExpireTime = 0;
    private $connected = false;
    private $lastResponse;
    private $userAgent;

    public function __construct(
        $clientId,
        $clientSecret,
        $token,
        $tokenExpireTime,
        $userAgent
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->token = $token;
        $this->tokenExpireTime = $tokenExpireTime;
        $this->userAgent = $userAgent;
    }


    public function fetchImage($imgs)
    {

        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $res = $this->sendRequest(
            'v2/files/fetch',
            $imgs,
            'POST'
        );

        //if ($res && $res->http_code == 200) {
        if ($res) {
            $this->connected = true;
            return $res;
        } else {
            $this->connected = false;
            $this->nullToken();
            $this->updateParentClassSettings();
            return false;
        }
    }


    public function uploadImage($fs_path, $sirv_path)
    {
        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        //fix dirname if uploaded throuth browser
        $path_info = pathinfo(rawurldecode($sirv_path));
        $path_info['dirname'] = $path_info['dirname'] == '.' ? '' : '/' . $path_info['dirname'];
        //$encoded_sirv_path = $path_info['dirname'] . '/' . rawurlencode($path_info['basename']);
        $encoded_sirv_path = rawurlencode($path_info['dirname'] . '/' . $path_info['basename']);
        //$encoded_sirv_path = $this->clean_symbols($encoded_sirv_path);

        $content_type = '';
        if (function_exists('mime_content_type')) {
            $content_type = mime_content_type($fs_path) !== false ? mime_content_type($fs_path) : 'application/octet-stream';
        } else {
            $content_type = "image/" . $path_info['extension'];
        }

        $headers = array(
            'Content-Type'   => $content_type,
            'Content-Length' => filesize($fs_path),
        );

        $res = $this->sendRequest(
            'v2/files/upload?filename=' . $encoded_sirv_path,
            file_get_contents($fs_path),
            'POST',
            '',
            $headers,
            true);

        if ($res && $res->http_code == 200) {
            $this->connected = true;
            return array('status' => 'uploaded');
        } else {
            $this->connected = false;
            $this->nullToken();
            $this->updateParentClassSettings();
            return array('status' => 'failed');
        }
    }


    private function clean_symbols($str)
    {
        $str = str_replace('%40', '@', $str);
        $str = str_replace('%5D', '[', $str);
        $str = str_replace('%5B', ']', $str);
        $str = str_replace('%7B', '{', $str);
        $str = str_replace('%7D', '}', $str);
        $str = str_replace('%2A', '*', $str);
        $str = str_replace('%3E', '>', $str);
        $str = str_replace('%3C', '<', $str);
        $str = str_replace('%24', '$', $str);
        $str = str_replace('%3D', '=', $str);
        $str = str_replace('%2B', '+', $str);
        $str = str_replace('%28', '(', $str);
        $str = str_replace('%29', ')', $str);

        return $str;
    }


    public function search($query, $from){
        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $data = array(
            'query' => $query,
            'from' => $from,
            'size' => 50,
            'sort' => array("basename.raw" => "asc")
        );

        $res = $this->sendRequest('v2/files/search', $data, 'POST');

        if ($res && $res->http_code == 200){
            $this->connected = true;

            if($res->result->total > $from + 50){
                $res->result->isContinuation = true;
                $res->result->from = $from + 50;
            }else{
                $res->result->isContinuation = false;
            }

            return $res->result;
        } else {
            $this->connected = false;
            $this->nullToken();
            $this->updateParentClassSettings();
            return false;
        }
    }


    public function deleteFile($filename)
    {
        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $res = $this->sendRequest(
            'v2/files/delete?filename=/'. $filename,
            array(),
            'POST'
        );

        return ($res && $res->http_code == 200);
    }


    public function setMetaTitle($filename, $title)
    {
        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $res = $this->sendRequest(
            'v2/files/meta/title?filename=' . $filename,
            array(
                'title' => $title
            ),
            'POST');

        return ($res && $res->http_code == 200);

    }


    public function setMetaDescription($filename, $description)
    {
        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $res = $this->sendRequest(
            'v2/files/meta/description?filename=' . $filename,
            array(
                'description' => $description
            ),
            'POST');

        return ($res && $res->http_code == 200);
    }


    public function configFetching($url, $status, $minify)
    {

        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $data = array();

        if ($status) {
            $data = array(
                'minify' => array(
                    "enabled" => $minify
                ),
                'fetching' => array(
                    "enabled" => true,
                    "type" => "http",
                    "http" => array(
                        "url" => $url,
                    ),
                )
            );
        } else {
            $data = array(
                'minify' => array(
                    "enabled" => false
                ),
                'fetching' => array(
                    "enabled" => false
                )
            );
        }

        $res = $this->sendRequest('v2/account', $data, 'POST');

        if ($res) {
            $this->connected = true;
            return true;
        } else {
            $this->connected = false;
            $this->nullToken();
            $this->updateParentClassSettings();
            return false;
        }
    }


    public function configCDN($status, $alias)
    {
        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $data = array(
            'aliases' => array(
                $alias => array(
                    "cdn" => $status
                )
            )
        );

        $res = $this->sendRequest('v2/account', $data, 'POST');

        if ($res) {
            $this->connected = true;
            return true;
        } else {
            $this->connected = false;
            $this->nullToken();
            $this->updateParentClassSettings();
            return false;
        }
    }

    public function preOperationCheck()
    {
        if ($this->connected) {
            return true;
        }

        if (empty($this->token) || $this->isTokenExpired()) {
            if (!$this->getNewToken()) {
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    public function isConnected()
    {
        return $this->connected;
    }

    public function getNewToken()
    {
        if (empty($this->clientId) || empty($this->clientSecret)) {
            $this->nullClientLogin();
            $this->nullToken();
            $this->updateParentClassSettings();
            return false;
        }
        $res = $this->sendRequest('v2/token', array(
            "clientId" => $this->clientId,
            "clientSecret" => $this->clientSecret,
        ));

        if ($res && $res->http_code == 200 && !empty($res->result->token) && !empty($res->result->expiresIn)) {
            $this->connected = true;
            $this->token = $res->result->token;
            $this->tokenExpireTime = time() + $res->result->expiresIn;
            $this->updateParentClassSettings();
            return $this->token;
        } else {
            $this->connected = false;
            if (!empty($res->http_code) && $res->http_code == 401) {
                $this->nullClientLogin();
            }
            $this->nullToken();
            $this->updateParentClassSettings();
            return false;
        }
    }


    private static function usersSortFunc($a, $b)
    {
        if ($a->alias == $b->alias) {
            return 0;
        }
        return ($a->alias < $b->alias) ? -1 : 1;
    }


    public function getUsersList($email, $password)
    {
        $res = $this->sendRequest('v2/token', array(
            "clientId" => $this->clientId_default,
            "clientSecret" => $this->clientSecret_default,
        ));

        if ($res && $res->http_code == 200 && !empty($res->result->token) && !empty($res->result->expiresIn)) {
            $res = $this->sendRequest('v2/user/accounts', array(
                "email" => $email,
                "password" => $password,
            ), 'POST', $res->result->token);
            if ($res && $res->http_code == 200 && !empty($res->result) && is_array($res->result)) {
                uasort($res->result, array('SirvAPIClient', 'usersSortFunc'));
                $res->result = array_values($res->result);
                return $res->result;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }


    public function getFolderOptions($filename)
    {
        $res = $this->sendRequest(
            'v2/files/options?filename=/'.rawurlencode($filename).'&withInherited=true',
            array(),
            'GET'
        );
        if ($res && $res->http_code == 200) {
            return $res->result;
        } else {
            return false;
        }
    }


    public function getFileStat($filename)
    {
        $res = $this->sendRequest(
            'v2/files/stat?filename=/'.rawurlencode($filename),
            array(),
            'GET'
        );

        if ($res && $res->http_code == 200) {
            return $res->result;
        } else {
            return false;
        }
    }


    public function getProfiles()
    {

        $res = $this->sendRequest(
            'v2/files/readdir?dirname=/Profiles',
            array(),
            'GET'
        );

        if ($res && $res->http_code == 200) {
            return $res->result;
        } else {
            return false;
        }
    }


    public function setFolderOptions($filename, $options)
    {
        $res = $this->sendRequest(
            'v2/files/options?filename=/'.rawurlencode($filename),
            $options,
            'POST'
        );
        return ($res && $res->http_code == 200);
    }


    public function registerAccount($email, $password, $firstName, $lastName, $alias)
    {
        $res = $this->sendRequest('v2/token', array(
            "clientId" => $this->clientId_default,
            "clientSecret" => $this->clientSecret_default,
        ));

        if ($res && $res->http_code == 200 && !empty($res->result->token) && !empty($res->result->expiresIn)) {
            $res = $this->sendRequest('v2/account', array(
                "email" => $email,
                "password" => $password,
                "firstName" => $firstName,
                "lastName" => $lastName,
                "alias" => $alias,
            ), 'PUT', $res->result->token);

            if ($res && $res->http_code == 200) {
                return true;
            } else {
                return false;
            }
        }
    }


    public function setupClientCredentials($token)
    {
        $res = $this->sendRequest('v2/rest/credentials', array(), 'GET', $token);
        if ($res && $res->http_code == 200 && !empty($res->result->clientId) && !empty($res->result->clientSecret)) {
            $this->clientId = $res->result->clientId;
            $this->clientSecret = $res->result->clientSecret;
            $this->getNewToken();
            $this->updateParentClassSettings();
            return true;
        } else {
            return false;
        }
    }


    public function setupS3Credentials($email = '')
    {
        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $res = $this->sendRequest('v2/account/users', array(), 'GET');

        if ($res && $res->http_code == 200 && !empty($res->result) && is_array($res->result) && count($res->result)) {
            $res_user = false;

            foreach ($res->result as $user) {
                $tmp_res = $this->sendRequest('v2/user?userId=' . $user->userId, array(), 'GET');
                if ($tmp_res && $tmp_res->http_code == 200 && $tmp_res->result->email == $email) {
                    $res_user = $tmp_res;
                    break;
                }
            }

            if ($res_user && $res_user->http_code == 200 &&
                !empty($res_user->result->s3Secret) && !empty($res_user->result->email)) {
                $res_alias = $this->sendRequest('v2/account', array(), 'GET');

                if ($res_alias && $res_alias->http_code == 200 &&
                    !empty($res_alias->result) && !empty($res_alias->result->alias)) {
                    $this->updateParentClassSettings(array(
                        'SIRV_AWS_BUCKET' => $res_alias->result->alias,
                        'SIRV_AWS_KEY' => $res_user->result->email,
                        'SIRV_AWS_SECRET_KEY' => $res_user->result->s3Secret,
                        //'SIRV_AWS_HOST' => 's3.sirv.com'
                    ));
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
            return true;
        } else {
            $this->updateParentClassSettings(array(
                'SIRV_AWS_BUCKET' => '',
                'SIRV_AWS_KEY' => '',
                'SIRV_AWS_SECRET_KEY' => '',
            ));
            return false;
        }
    }


    public function updateParentClassSettings($extra_options = array())
    {
        if(function_exists('update_option')){
            update_option('SIRV_CLIENT_ID', $this->clientId);
            update_option('SIRV_CLIENT_SECRET', $this->clientSecret);
            update_option('SIRV_TOKEN', $this->token);
            update_option('SIRV_TOKEN_EXPIRE_TIME', $this->tokenExpireTime);
            if (count($extra_options)){
                foreach ($extra_options as $option => $value) {
                    update_option($option, $value);
                }
            }
        }
        return true;
    }


    public function nullClientLogin()
    {
        $this->clientId = '';
        $this->clientSecret = '';
        $this->updateParentClassSettings(array(
            'SIRV_AWS_BUCKET' => '',
            'SIRV_AWS_KEY' => '',
            'SIRV_AWS_SECRET_KEY' => '',
        ));
    }


    public function nullToken()
    {
        $this->token = '';
        $this->tokenExpireTime = 0;
    }


    public function isTokenExpired()
    {
        return $this->tokenExpireTime < time();
    }


    public function getAccountInfo()
    {
        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $result = $this->sendRequest('v2/account', array(), 'GET');

        if (!$result || empty($result->result) || $result->http_code != 200 || empty($result->result)) {
            $this->connected = false;
            $this->nullToken();
            $this->updateParentClassSettings();
            return false;
        }

        return $result->result;
    }


    public function getFormatedFileSize($bytes, $fileName = "", $decimal = 2, $bytesInMM = 1000)
    {
        if (!empty($fileName)) {
            $bytes = filesize($fileName);
        }

        $sign = ($bytes>=0)?'':'-';
        $bytes = abs($bytes);

        if (is_numeric($bytes)) {
            $position = 0;
            $units = array( " Bytes", " KB", " MB", " GB", " TB" );
            while ($bytes >= $bytesInMM && ($bytes / $bytesInMM) >= 1) {
                $bytes /= $bytesInMM;
                $position++;
            }
            return ($bytes==0)?'-':$sign.round($bytes, $decimal).$units[$position];
        } else {
            return "-";
        }
    }


    public function getStorageInfo()
    {
        $preCheck = $this->preOperationCheck();
        if (!$preCheck) {
            return false;
        }

        $storageInfo = array();

        $result = $this->sendRequest('v2/account', array(), 'GET');
        $result_storage = $this->sendRequest('v2/account/storage', array(), 'GET');

        if (!$result || empty($result->result) || $result->http_code != 200
            || !$result_storage->result || empty($result->result) || $result_storage->http_code != 200) {
            $this->connected = false;
            $this->nullToken();
            $this->updateParentClassSettings();
            return false;
        }

        $result = $result->result;
        $result_storage = $result_storage->result;

        if (isset($result->alias)) {
            $storageInfo['account'] = $result->alias;

            $billing = $this->sendRequest('v2/billing/plan', array(), 'GET');

            $billing->result->dateActive = preg_replace(
                '/.*([0-9]{4}\-[0-9]{2}\-[0-9]{2}).*/ims',
                '$1',
                $billing->result->dateActive
            );

            $planEnd = strtotime('+30 days', strtotime($billing->result->dateActive));
            $now = time();

            $datediff = (int) round(($planEnd - $now) / (60 * 60 * 24));

            $until = ($planEnd > $now) ? ' (' . $datediff . ' day' . ($datediff > 1 ? 's' : '') . ' left)' : '';

            if ($planEnd < $now) {
                $until = '';
            }

            $storageInfo['plan'] = array(
                'name' => $billing->result->name,
                'trial_ends' => preg_match('/trial/ims', $billing->result->name) ?
                    'until ' . date("j F", strtotime('+30 days', strtotime($billing->result->dateActive))) . $until
                    : '',
                'storage' => $billing->result->storage,
                'storage_text' => $this->getFormatedFileSize($billing->result->storage),
                'dataTransferLimit' => isset($billing->result->dataTransferLimit) ?
                    $billing->result->dataTransferLimit : '',
                'dataTransferLimit_text' => isset($billing->result->dataTransferLimit) ?
                    $this->getFormatedFileSize($billing->result->dataTransferLimit) : '&#8734',
            );

            $storage = $this->sendRequest('v2/account/storage', array(), 'GET');

            $storage->result->plan = $storage->result->plan + $storage->result->extra;

            $storageInfo['storage'] = array(
                'allowance' => $storage->result->plan,
                'allowance_text' => $this->getFormatedFileSize($storage->result->plan),
                'used' => $storage->result->used,
                'available' => $storage->result->plan - $storage->result->used,
                'available_text' => $this->getFormatedFileSize($storage->result->plan - $storage->result->used),
                'available_percent' => number_format(
                    ($storage->result->plan - $storage->result->used) / $storage->result->plan * 100,
                    2,
                    '.',
                    ''
                ),
                'used_text' => $this->getFormatedFileSize($storage->result->used),
                'used_percent' => number_format($storage->result->used / $storage->result->plan * 100, 2, '.', ''),
                'files' => $storage->result->files,
            );

            $storageInfo['traffic'] = array(
                'allowance' => isset($billing->result->dataTransferLimit) ? $billing->result->dataTransferLimit : '',
                'allowance_text' => isset($billing->result->dataTransferLimit) ?
                $this->getFormatedFileSize($billing->result->dataTransferLimit) : '&#8734',
            );

            $dates = array(
                'This month' => array(
                    date("Y-m-01"),
                    date("Y-m-t"),
                ),
                date("F Y", strtotime("first day of -1 month")) => array(
                    date("Y-m-01", strtotime("first day of -1 month")),
                    date("Y-m-t", strtotime("last day of -1 month")),
                ),
                date("F Y", strtotime("first day of -2 month")) => array(
                    date("Y-m-01", strtotime("first day of -2 month")),
                    date("Y-m-t", strtotime("last day of -2 month")),
                ),
                date("F Y", strtotime("first day of -3 month")) => array(
                    date("Y-m-01", strtotime("first day of -3 month")),
                    date("Y-m-t", strtotime("last day of -3 month")),
                ),
            );

            $dataTransferLimit = isset($billing->result->dataTransferLimit) ?
            $billing->result->dataTransferLimit : PHP_INT_MAX;

            $count = 0;
            foreach ($dates as $label => $date) {
                $traffic = $this->sendRequest('v2/stats/http?from=' . $date[0] . '&to=' . $date[1], array(), 'GET');

                if (!$traffic || $traffic->http_code != 200) {
                    $this->connected = false;
                    $this->nullToken();
                    $this->updateParentClassSettings();
                    return false;
                }

                unset($traffic->http_code);

                $traffic = (array)$traffic->result;

                $storageInfo['traffic']['traffic'][$label]['size'] = 0;
                $storageInfo['traffic']['traffic'][$label]['order'] = $count++;

                if (count($traffic)) {
                    foreach ($traffic as $v) {
                        $storageInfo['traffic']['traffic'][$label]['size'] += (isset($v->total->size))
                        ? $v->total->size : 0;
                    }
                }
                $storageInfo['traffic']['traffic'][$label]['percent'] = number_format(
                    $storageInfo['traffic']['traffic'][$label]['size'] / $dataTransferLimit * 100,
                    2,
                    '.',
                    ''
                );
                $storageInfo['traffic']['traffic'][$label]['percent_reverse'] = number_format(
                    100 - $storageInfo['traffic']['traffic'][$label]['size'] / $dataTransferLimit * 100,
                    2,
                    '.',
                    ''
                );
                $storageInfo['traffic']['traffic'][$label]['size_text'] =
                    $this->getFormatedFileSize($storageInfo['traffic']['traffic'][$label]['size']);
            }
        }

        $result = $this->sendRequest('v2/account/limits', array(), 'GET');

        if ($result && !empty($result->result) && $result->http_code == 200) {
            $storageInfo['limits'] = $result->result;
            $storageInfo['limits'] = (array) $storageInfo['limits'];
            //$date = new DateTime();
            //$timeZone = $date->getTimezone();
            foreach ($storageInfo['limits'] as $type => $value) {
                $storageInfo['limits'][$type] = (array) $value;
                $value = (array) $value;
                /* $dt = new DateTime('@' . $value['reset']);
                $dt->setTimeZone(new DateTimeZone($timeZone->getName()));
                $storageInfo['limits'][$type]['reset_str'] = $dt->format("H:i:s");*/
                $storageInfo['limits'][$type]['reset_timestamp'] = (int)$value['reset'];
                $storageInfo['limits'][$type]['reset_str'] = date('H:i:s e', $value['reset']);
                $storageInfo['limits'][$type]['count_reset_str'] = $this->calcTime((int) $value['reset']);
                //$storageInfo['limits'][$type]['used'] = (round($value['count'] / $value['limit'] * 10000) / 100) . '%';
                $storageInfo['limits'][$type]['used'] = $value['count'] == 0 || $value['limit'] == 0 ? 0 : (round($value['count'] / $value['limit'] * 10000) / 100) . '%';
                $storageInfo['limits'][$type]['type'] = $type;
            }
            //$storageInfo['limits'] = array_chunk($storageInfo['limits'], (int) count($storageInfo['limits']) / 2);
        }

        return $storageInfo;
    }

    public function calcTime($end){
        $mins = round(($end - time())/60);

        return "$mins minutes";
    }

    public function getContent($path='/', $continuation='')
    {
        $preCheck = $this->preOperationCheck();
            if (!$preCheck) {
                return false;
            }

            $params = $continuation !== ''
                ? 'dirname='.$path.'&continuation='.$continuation
                : 'dirname='.$path;

        $content = $this->sendRequest('v2/files/readdir?' . $params, array(), 'GET');
        if (!$content || $content->http_code != 200) {
            $this->connected = false;
            $this->nullToken();
            $this->updateParentClassSettings();
            return false;
        }

        return $content->result;
    }

    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    private function muteRequests($timestamp)
    {
        update_option('SIRV_MUTE', $timestamp, 'no');
    }

    public function isMuted()
    {
        return ((int)get_option('SIRV_MUTE') > time());
    }

    private function sendRequest($url, $data, $method = 'POST', $token = '', $headers = null, $isFile = false)
    {

        if ($this->isMuted()) {
            $this->curlInfo = array('http_code' => 429);
            return false;
        }

        if (is_null($headers)) $headers = array();

        if (!empty($token)) {
            $headers['Authorization'] = "Bearer " . ((!empty($token)) ? $token : $this->token);
        } else {
            $headers['Authorization'] = "Bearer " . $this->token;
        }
        if(!array_key_exists('Content-Type', $headers)) $headers['Content-Type'] = "application/json";

        foreach ($headers as $k => $v){
            $headers[$k] = "$k: $v";
        }

        //$fp = fopen(dirname(__FILE__) . '/curl_errorlog.txt', 'w');

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.sirv.com/" . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => 0.1,
            CURLOPT_TIMEOUT => 0.1,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => (!$isFile) ? json_encode($data) : $data,
            CURLOPT_HTTPHEADER => $headers,
            //CURLOPT_SSL_VERIFYPEER => false,
            //CURLOPT_VERBOSE => true,
            //CURLOPT_STDERR => $fp,
        ));

        $result = curl_exec($curl);
        $info = curl_getinfo($curl);

        if ($info['http_code'] == 429 &&
            preg_match('/Retry after ([0-9]{4}\-[0-9]{2}\-[0-9]{2}.*?\([a-z]{1,}\))/ims', $result, $m)) {
            $time = strtotime($m[1]);
            $this->muteRequests($time);
        }

        $response = (object) $info;
        $response->result = json_decode($result);
        $response->error = curl_error($curl);

        $this->lastResponse = $response;

        curl_close($curl);
        //fclose($fp);

        return $response;
    }
}
