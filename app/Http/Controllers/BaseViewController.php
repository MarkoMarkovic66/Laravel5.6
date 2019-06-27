<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Log;
use Auth;
use App\Enums\SessionValiableType;

class BaseViewController extends Controller
{
	//画面描画パラメータのコレクション(描画用データおよび権限情報等の一式を格納)
	protected $view_params;

	public function __construct()
	{
  		$this->view_params = collect();
	}

	//ログインユーザ情報を取得
	protected function getLoginUser() {
        $login_user = session(SessionValiableType::LOGIN_ACCOUNT_INFO);
		return $login_user->all();
	}

	/**
     * CSVダウンロード
     * @param array $list
     * @param array $header
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    protected function download($list, $header, $filename)
    {
        if (count($header) > 0) {
            array_unshift($list, $header);
        }
        $stream = fopen('php://temp', 'r+b');
        foreach ($list as $row) {
            fputcsv($stream, $row);
        }
        rewind($stream);
        $csv = str_replace(PHP_EOL, "\r\n", stream_get_contents($stream));
        $csv = mb_convert_encoding($csv, 'SJIS-win', 'UTF-8');
        $headers = array(
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
        );
        return \Response::make($csv, 200, $headers);
    }

    protected function putCsvForDownload($dataList, $filename)
    {
        //PHPのテンポラリに読み書きの準備をする
        $fp=fopen('php://temp','w+');

        foreach($dataList as $data){
            //テンポラリにCSV形式で書き込む
            foreach($data as $v){
                fputcsv($fp, $v, ',', '"');
            }
        }

        //ファイルポインタを一番先頭に戻す
        rewind($fp);
        //ファイルポインタの今の位置から全てを読み込み文字列へ代入
        $csv=stream_get_contents($fp);
        //SJISに変える
        $csv=mb_convert_encoding($csv,'SJIS',mb_internal_encoding());
        //ファイルポインタを閉じる
        fclose($fp);
        //渡されたファイル名の拡張子やパスを切り落とす
        $file_name=basename($filename);
        //ダウンロードヘッダ定義
        header('Content-Disposition:attachment; filename="'.$file_name.'.csv"');
        header('Content-Type:application/octet-stream');
        header('Content-Length:'.strlen($csv));
        echo $csv;
        exit;

    }


}



