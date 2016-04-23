<?php

class PlateRecognition
{

    protected $filename;
    const API_URL = 'https://api.openalpr.com/v1/recognize';
    const API_METHOD = 'POST';
    const SECRET_KEY = 'sk_a7b029cfb13318bef889e4b0';
    const COUNTRY = 'sg';
    const UPLOAD_DIR = '../../uploads';

    public function __construct($file_obj, $key) {
        $this->filename = $this->move_file($file_obj[$key]);
    }

    protected function move_file($file) {
        if (!$file['error']) {

            $tmp = $file['tmp_name'];
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = self::UPLOAD_DIR.'/'.time().".$extension";

            if (!is_dir(self::UPLOAD_DIR)) {
                mkdir(self::UPLOAD_DIR);
            }
            move_uploaded_file($tmp, $filename);
            return $filename;
        }
    }

    public function get_plate_number() {
        $curl_file = curl_file_create($this->filename);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 600,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => self::API_METHOD,
            CURLOPT_POSTFIELDS => [
                'image' => $curl_file,
                'tasks' => 'plate',
                'secret_key' => self::SECRET_KEY,
                'country' => self::COUNTRY,
                'topn' => 1
            ]
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        $plate_no = false;
        if (!$err) {
            $plate_data = json_decode($response);

            try {
                $plate_no = $plate_data->plate->results[0]->plate;
            } catch (Exception $e) {
                echo 'Caught exception: '.$e->getMessage()."\n";
            }
        }

        // Delete file
        unlink($this->filename);

        return $plate_no;
    }

}
