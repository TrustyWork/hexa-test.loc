<?php

namespace TrustyWork\ImageDownloader\Test;

use PHPUnit_Framework_TestCase;
use TrustyWork\ImageDownloader\Downloader;

class DownloaderTest extends PHPUnit_Framework_TestCase
{

    public function testValidationImageList1()
    {

        $obj = new Downloader();

        $this->setExpectedException('Exception', '', Downloader::ERR_EMPTY_IMAGES_LIST);
        $obj->setImages('');
    }

    public function testValidationImageList2()
    {

        $obj = new Downloader();

        $this->setExpectedException('Exception', '', Downloader::ERR_EMPTY_IMAGES_LIST);
        $obj->setImages([]);
    }

    public function testValidationImageList3()
    {

        $obj = new Downloader();

        $this->setExpectedException('Exception', '', Downloader::ERR_WRONG_IMAGES_LIST);
        $obj->setImages(new \DateTime());
    }

    public function testValidationImageList4()
    {

        $obj = new Downloader();

        $images = ['asdasd', '', 'asdasd', 'aaa'];
        $obj->setImages($images);
        $this->assertEquals(['asdasd', 'aaa'], $obj->getImages(), 'filter empty and non unique value');
    }

    public function testValidationSavePathList1()
    {

        $obj = new Downloader();

        $this->setExpectedException('Exception', '', Downloader::ERR_EMPTY_SAVE_PATH);
        $obj->setSavePath('');
    }

    public function testValidationSavePathList2()
    {

        $obj = new Downloader();

        $this->setExpectedException('Exception', '', Downloader::ERR_NOT_READABLE_SAVE_PATH);
        $path = 'asdasdasd/asdasfgfg/fdgdfgqwe/ewqe';
        $obj->setSavePath($path);
    }

    public function testValidationSavePathList3()
    {
        $obj = new Downloader();

        $this->setExpectedException('Exception', '', Downloader::ERR_WRONG_SAVE_PATH);
        $path = __FILE__;
        $obj->setSavePath($path);
    }

    public function testValidationSavePathList4()
    {

        $obj = new Downloader();

        $this->setExpectedException('Exception', '', Downloader::ERR_NOT_WRITABLE_SAVE_PATH);
        $path = __DIR__ . '/NOT_WRITABLE_DIR';
        $obj->setSavePath($path);
    }

    public function testValidationSavePathList5()
    {

        $obj = new Downloader();

        $path = __DIR__;
        $obj->setSavePath($path);
        $this->assertEquals($path, $obj->getSavePath(), 'set save path');
    }

    public function testValidationOptions1()
    {

        $obj = new Downloader();

        $options = 'asdasdasda';

        $this->setExpectedException('Exception', '', Downloader::ERR_WRONG_OPTIONS);
        $obj->setOptions($options);
    }

    public function testValidationOptions2()
    {

        $obj = new Downloader();

        $options = [
            'max_size' => -10
        ];

        $this->setExpectedException('Exception', '', Downloader::ERR_WRONG_KEY_OPTIONS);
        $obj->setOptions($options);
    }

    public function testValidationOptions3()
    {

        $obj = new Downloader();

        $options = [
            'asdasd1' => 'sdafasda1'
            ,
            'asdasd2' => 'sdafasda2'
            ,
            'asdasd3' => 'sdafasda3'
        ];

        $obj->setOptions($options);
        $this->assertEquals($options, $obj->getOptions(), 'set options');
    }

    public function testRun1()
    {

        $savePath = __DIR__ . '/TEST_DOWNLOAD';
        $image = '9534-059-345';

        $obj = new Downloader();

        $obj->setSavePath($savePath);
        $obj->setImages($image);

        $this->assertFalse($obj->run(), 'bad link');
        $this->assertTrue($obj->isError(), 'is error');
        $this->assertEquals([$image => Downloader::ERR_BAD_LINK_IMAGE], $obj->getErrors(), 'errors format');
    }

    public function testRun2()
    {
        $savePath = __DIR__ . '/TEST_DOWNLOAD';
        $image = 'http://slavpeople.com/images/news_posters/dsfsdfdsfsdf';

        $obj = new Downloader();
        $obj->setSavePath($savePath);
        $obj->setImages($image);
        $obj->run();

        $this->assertEquals([$image => Downloader::ERR_404_LINK_IMAGE], $obj->getErrors(), 'link 404');
    }

    public function testRun3()
    {
        $savePath = __DIR__ . '/TEST_DOWNLOAD';
        $image = 'http://slavpeople.com/';

        $obj = new Downloader();
        $obj->setSavePath($savePath);
        $obj->setImages($image);
        $obj->run();

        $this->assertEquals([$image => Downloader::ERR_BAD_FORMAT_IMAGE], $obj->getErrors(), 'errors image format');
    }

    public function testRun4()
    {
        $savePath = __DIR__ . '/TEST_DOWNLOAD';
        $image = 'http://slavpeople.com/images/news_posters/kupit-google-za-12-dollarov_14437941531030.jpg';
        $size = 1;
        $options = ['max_size' => $size];

        $obj = new Downloader();
        $obj->setSavePath($savePath);
        $obj->setImages($image);
        $obj->setOptions($options);
        $obj->run();

        $this->assertEquals([$image => Downloader::ERR_TO_BIG_IMAGE], $obj->getErrors(), 'max size');
    }

    public function testRun5()
    {
        $obj = new Downloader();
        $this->setExpectedException('Exception', '', Downloader::ERR_EMPTY_IMAGES_LIST);
        $obj->run();
    }

    public function testRun6()
    {
        $image = 'http://slavpeople.com/images/news_posters/kupit-google-za-12-dollarov_14437941531030.jpg';

        $obj = new Downloader();
        $obj->setImages($image);
        $this->setExpectedException('Exception', '', Downloader::ERR_EMPTY_SAVE_PATH);
        $obj->run();
    }

    public function testRun7()
    {
        $savePath = __DIR__ . '/TEST_DOWNLOAD';
        $hash = '4c69177da563756eea6895acc01041b9d1ddd836';
        $imagePath = "{$savePath}/{$hash}.jpeg";
        $image = 'http://slavpeople.com/images/news_posters/kupit-google-za-12-dollarov_14437941531030.jpg';

        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        $obj = new Downloader();
        $obj->setSavePath($savePath);
        $obj->setImages($image);
        $obj->run();

        if (!file_exists($imagePath)) {
            $this->fail('download image');
        }
    }
}