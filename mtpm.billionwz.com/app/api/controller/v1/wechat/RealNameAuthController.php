<?php
namespace app\api\controller\v1\wechat;

use think\Request;
use app\services\wechat\RealNameAuthService;

class RealNameAuthController
{
    public function __construct(RealNameAuthService $services)
    {
        $this->services = $services;
    }
    
    
    public function recognize()
    {
        //。return json(['code' => 200, 'msg' => 1]);
        // 腾讯云API配置
        $secretId = config('tencent.secret_id');
        $secretKey = config('tencent.secret_key');
        $service = 'ocr'; // 服务名
        $host = 'ocr.tencentcloudapi.com'; // 主机名
        $action = 'IDCardOCR'; // 接口名
        $version = '2018-11-19'; // 接口版本
        $region = 'ap-guangzhou'; // 区域
        
        // 获取上传的身份证图片
        $file = request()->file('file');
        $cardSide = request()->param('cardSide');
        //return json(['code' => 200, 'msg' => $cardSide]);
        if (!$file) {
            return json(['code' => 400, 'msg' => '请上传身份证图片']);
        }
        
        try {
            // 读取图片并转为base64
            $imagePath = $file->getRealPath();
            $imageData = file_get_contents($imagePath);
            $imageBase64 = base64_encode($imageData);
            
            // 构造请求数据
            $payload = json_encode([
                'ImageBase64' => $imageBase64,
                'CardSide' => $cardSide // FRONT-身份证正面，BACK-身份证背面
            ]);
            
            // ************* 步骤1：拼接规范请求串 *************
            $httpRequestMethod = 'POST';
            $canonicalUri = '/';
            $canonicalQueryString = '';
            $canonicalHeaders = "content-type:application/json\nhost:{$host}\n";
            $signedHeaders = 'content-type;host';
            $hashedRequestPayload = hash('SHA256', $payload);
            
            $canonicalRequest = implode("\n", [
                $httpRequestMethod,
                $canonicalUri,
                $canonicalQueryString,
                $canonicalHeaders,
                $signedHeaders,
                $hashedRequestPayload
            ]);
            
            // ************* 步骤2：拼接待签名字符串 *************
            $algorithm = 'TC3-HMAC-SHA256';
            $timestamp = time();
            $date = gmdate('Y-m-d', $timestamp);
            $credentialScope = "{$date}/{$service}/tc3_request";
            $hashedCanonicalRequest = hash('SHA256', $canonicalRequest);
            
            $stringToSign = implode("\n", [
                $algorithm,
                $timestamp,
                $credentialScope,
                $hashedCanonicalRequest
            ]);
            
            // ************* 步骤3：计算签名 *************
            
            $secret_date = $this->services-> sign("TC3".$secretKey, $date);
            $secret_service = $this->services->sign($secret_date, $service);
            $secret_signing = $this->services->sign($secret_service, "tc3_request");
            $signature = hash_hmac("sha256", $stringToSign, $secret_signing);
            
            
            // ************* 步骤4：拼接Authorization *************
            $authorization = implode(', ', [
                $algorithm . ' Credential=' . $secretId . '/' . $credentialScope,
                'SignedHeaders=' . $signedHeaders,
                'Signature=' . $signature
            ]);
            
            // ************* 步骤5：构造请求头 *************
            $headers = [
                'Authorization: ' . $authorization,
                'Content-Type: application/json',
                'Host: ' . $host,
                'X-TC-Action: ' . $action,
                'X-TC-Timestamp: ' . $timestamp,
                'X-TC-Version: ' . $version,
                'X-TC-Region: ' . $region
            ];
            
            // ************* 步骤6：发送请求 *************
            $url = 'https://' . $host;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new \Exception(curl_error($ch));
            }
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if (isset($result['Response']['Error'])) {
                return json([
                    'code' => 500,
                    'msg' => '识别失败: ' . $result['Response']['Error']['Message']
                ]);
            }
            
            // 返回识别结果
            return json([
                'code' => 200,
                'msg' => '识别成功',
                'data' => $result['Response']
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => '识别失败: ' . $e->getMessage()
            ]);
        }
    }
}