<?php
use \Appkita\Spark\Controller;
class Api extends Controller {
    public function index() {
        die(json_encode($this->request->getPost()));
        die(json_encode(func_get_args()));
    }

    public function upload() {

    }
}