<?php
/**
 * CreateTime: 2019/8/6 14:44
 * Author: hhh
 * Description:
 */
namespace App\HttpController\Chat;

use App\HttpController\Base;
use App\Utility\Pool\MysqlPool;
use App\Utility\Pool\RedisPool;
use EasySwoole\Validate\Validate;

class Func extends Base
{
    public function uploadImg()
    {
        if($this->request()->getMethod() == 'POST'){
            $img_file = $this->request()->getUploadedFile('file');
            if(!$img_file){
                return $this->writeJson(500, null, '请选择上传的图片');
            }

            if($img_file->getSize() > 1024*1024*4){
                return $this->writeJson(500, null, '图片不能大于4M!');
            }

            $MediaType = explode("/", $img_file->getClientMediaType());
            $MediaType = $MediaType[1] ?? "";
            if(!in_array($MediaType, ['png', 'jpg', 'gif', 'jpeg', 'pem', 'ico'])){
                return $this->writeJson(500, null, '图片类型不正确！');
            }

            $path =  '/Static/upload/img/';
            $dir  = EASYSWOOLE_ROOT.$path;
            $filename = uniqid().$img_file->getClientFileName();

            if(!is_dir($dir)){
                mkdir($dir, 0777, true);
            }

            $flag = $img_file->moveTo($dir.$filename);

            $data = [
                'name' => $filename,
                'src'  => $path.$filename
            ];

            if($flag){
                return $this->writeJson(0, $data, '上传成功');
            }else{
                return $this->writeJson(500, null, '上传失败');
            }

        }
    }
  
    public function uploadFile()
    {
        if($this->request()->getMethod() == 'POST'){
            $up_file = $this->request()->getUploadedFile('file');
            if(!$up_file){
                return $this->writeJson(500, null, '请选择上传的文件');
            }
            if($up_file->getSize() > 1024*1024*4){
                return $this->writeJson(500, null, '文件不能大于4M！');
            }
            $mediaType = explode('/', $up_file->getClientMediaType());
            $this->writeLog($mediaType);
            $path = '/Static/upload/file/';
            $dir  = EASYSWOOLE_ROOT.$path;
            $filename = uniqid().$up_file->getClientFileName();
            if(!is_dir($dir)){
                mkdir($dir, 0777, true);
            }
            $flag = $up_file->moveTo($dir.$filename);
            $data = [
                'name' => $filename,
                'src'  => $path.$filename
            ];
            if($flag){
                return $this->writeJson(0, $data, '上传成功');
            }else{
                return $this->writeJson(500, null, '上传失败');
            }
        }
    }
}