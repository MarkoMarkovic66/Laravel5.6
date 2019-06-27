<?php
namespace App\Services;

use AWS;
use Log;
use App\Utils\CommonUtils;

/**
 * S3関連Service
 */
class S3Service {
	private $s3BucketName;
	private $s3PresignedUrlExpires;

    public function __construct(){
        $this->s3BucketName = env('AWS_S3_BUCKET_NAME');
        $this->s3PresignedUrlExpires = env('AWS_S3_PRESINGNED_URL_EXPIRES');
    }

    /**
     * s3からファイルを取得
     * @param string $s3Key
     * @return object
     * @throws Exception
     */
	public function getObject($s3Key)
	{
        try {
            $s3 = AWS::createClient('s3');
            $object = $s3->getObject([
                'Bucket' => $this->s3BucketName,
                'Key'    => $s3Key
            ]);
            return $object;

        } catch (\Exception $e) {
            Log::error('S3 getObject: error: '.$e->getMessage());
            throw $e;
        }
  	}

    /**
     * s3へファイルをアップロード
     * @param string $s3Key
     * @param string $filePath
     * @return boolean
     * @throws Exception
     */
	public function postObject($s3Key, $filePath)
	{
        try {
            $s3 = AWS::createClient('s3');
            $s3->putObject(array(
                'Bucket'     => $this->s3BucketName,
                'Key'        => $s3Key,
                'SourceFile' => $filePath,
            ));
            return true;

        } catch (\Exception $e) {
            Log::error('S3 postObject: error: '.$e->getMessage());
            throw $e;
        }
	}

    /**
     * s3Keyを指定して事前署名付きURLを取得
     * @param type $s3Key
     * @return string
     * @throws Exception
     */
	public function getPreSignedUrlByS3Key($s3Key)
	{
        try {
            $s3 = AWS::createClient('s3');
            $cmd = $s3->getCommand('GetObject', [
                'Bucket' => $this->s3BucketName,
                'Key'    => $s3Key,
            ]);
            $request = $s3->createPresignedRequest($cmd, $this->s3PresignedUrlExpires);
            $presignedUrl = (string) $request->getUri();
            return $presignedUrl;

        } catch (\Exception $e) {
            Log::error('S3 getPreSignedUrlByS3Key: error: '.$e->getMessage());
            throw $e;
        }
	}

    /**
     * S3に格納されているオブジェクトのS3Keyリストを取得する
     * S3コマンドの制限によりMax1000件(デフォルト)であることに注意
     * @return array
     * @throws \Exception
     */
	public function getS3ObjectKeyList()
	{
        try {
            $s3 = AWS::createClient('s3');
            $s3objs = $s3->listObjects([
                        'Bucket' => $this->s3BucketName,
                    ]);

            $s3KeyList = [];
            foreach ($s3objs['Contents'] as $s3Obj) {
                $s3Key = $s3Obj['Key'];
                $s3KeyList[] = $s3Key;
            }
            return $s3KeyList;

        } catch (\Exception $e) {
            Log::error('S3 getS3ObjectKeyList: error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * GetInfoS3 function
     *
     * @return void
     */
    public static function getInfoS3()
    {
        $infoS3 = [
            'key'     => \Config::get('aws.key'),
            'secret'  => \Config::get('aws.secret'),
            'bucket'  => \Config::get('aws.bucket'),
            'expires' => \Config::get('aws.expires'),
            'region'  => \Config::get('aws.region'),
        ];

        //setup result
        $result = CommonUtils::apiResultOK(
            $infoS3
        );
        return $result;
    }


}

