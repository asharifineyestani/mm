<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Programs;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\Log;

class EmailController extends Controller
{

    public $current_email;
    public $need_send = FALSE;
    public $readed = FALSE;

    private function get_email($message_number = 2)
    {
        $connection = imap_open("{mail.morabiman.com:110/pop3/novalidate-cert}INBOX", "apimail@morabiman.com", "953254");


//$message_number=1;
        $structure = imap_fetchstructure($connection, 1);


        var_dump($structure) ;
        die;

        if (!$structure) {
            return FALSE;
        } else {
            $this->readed = true;
        }
        $attachments = array();
        if (isset($structure->parts) && count($structure->parts)) {

            for ($i = 0; $i < count($structure->parts); $i++) {


//			var_dump($structure->parts[$i]);
                if ($structure->parts[$i]->subtype == "X-INI" || $structure->parts[$i]->subtype == "OCTET-STREAM") {
                    $attachments[$i] = array(
                        'is_attachment' => false,
                        'filename' => '',
                        'name' => '',
                        'attachment' => ''
                    );

                    if ($structure->parts[$i]->ifdparameters) {

                        foreach ($structure->parts[$i]->dparameters as $object) {
                            if (strtolower($object->attribute) == 'filename') {
                                $attachments[$i]['is_attachment'] = true;
                                $attachments[$i]['filename'] = $object->value;
                            }
                        }
                    }

                    if ($structure->parts[$i]->ifparameters) {
                        foreach ($structure->parts[$i]->parameters as $object) {
                            if (strtolower($object->attribute) == 'name') {
                                $attachments[$i]['is_attachment'] = true;
                                $attachments[$i]['name'] = $object->value;
                            }
                        }
                    }

                    if ($attachments[$i]['is_attachment']) {
                        $attachments[$i]['attachment'] = imap_fetchbody($connection, $message_number, $i + 1);
                        if ($structure->parts[$i]->encoding == 3) { // 3 = BASE64
                            $attachments[$i]['attachment'] = iconv('windows-1256', 'utf-8', base64_decode($attachments[$i]['attachment']));
                        } elseif ($structure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                            $attachments[$i]['attachment'] = iconv('windows-1256', 'utf-8', quoted_printable_decode($attachments[$i]['attachment']));
                        }
                    }
                }
            }
        }
        $array = array();
        if (count($attachments) > 0) {
            $this->need_send = true;
            foreach ($attachments as $attachment) {
//	$array[$attachment['filename']][$lines[0]]=array();
                $text = $attachment['attachment'];
                $parafs = explode('[', $text);

                foreach ($parafs as $paraf) {
                    if (strlen($paraf) > 1) {
                        $title = explode(']', $paraf);
                        $lines = explode(PHP_EOL, $title[1]);
                        foreach ($lines as $line) {
                            if (strlen($line) > 1) {
                                $value = explode('=', $line);
                                $array[$attachment['filename']][$title[0]][$value[0]] = trim($value[1]);
                            }
                        }
                    }

                }
//	var_dump($endline);die;
            }
            return $array;
        } else {
            $this->need_send = false;
            return array();
        }
    }

    public function callAPI($method, $url, $data)
    {
        $curl = curl_init();

        switch ($method) {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }

        // OPTIONS:
        curl_setopt($curl, CURLOPT_URL, $url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        // EXECUTE:
        $result = curl_exec($curl);
        if (!$result) {
            die("Connection Failure");
        }
        curl_close($curl);
        echo PHP_EOL . 'send:' . PHP_EOL;
        var_dump($data);
        echo PHP_EOL . 'recive:' . PHP_EOL;
        var_dump($result);
        return $result;
    }

    /**
     *
     *
     * @return
     */
    public function get_new_email()
    {
        $myfile = fopen("last_email.txt", "r") or die("Unable to open file!");
        $last_email = fread($myfile, filesize("last_email.txt"));

        $this->current_email = $last_email + 1;
        $datas = $this->get_email($this->current_email);
//var_dump($datas);die;
        if ($datas === false) {
            return false;
        } else {
            $myfile = fopen("last_email.txt", "w") or die("Unable to open file!");
            fwrite($myfile, $this->current_email);
            fclose($myfile);
            return $datas;
        }
    }


    public function store(Request $request)
    {




    }


    public function index()
    {
        $datas = $this->get_new_email();

        return $datas;

        if ($this->readed) {
            echo 'email number ' . $this->current_email . ' was read' . PHP_EOL;
            if ($this->need_send) {
                $data['data'] = json_encode($datas);
                $data['checksum'] = md5(json_encode($datas));
                $data['email_id'] = $this->current_email;

                $request = $this->callAPI("POST", 'http://prof.nikanproject.ir/programs', $data);
                die(' and sended successfully');
            } else {
                die(' and there is not any attachment so no need to send through api');
            }

        } else {
            die ('there is no new email ' . PHP_EOL . 'current email=' . $this->current_email);
        }
    }


    public function show($id)
    {

    }


}
