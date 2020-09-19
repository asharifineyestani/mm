<?php

namespace App\Http\Controllers\Emails;

use App\Helpers\EmailAdapter;
use App\Models\Received;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Validator;

ini_set('display_errors', true);
error_reporting(E_ERROR);

class ReceivedController extends Controller
{

    public function store(Request $request)
    {

//        return 9;
        Log::emergency($request->all()); #todo test


        $data['status'] = false;
        $data['message'] = null;
        $data['errors'] = null;

        $validator = Validator::make($request->all(), [
            'files' => 'required|string',
        ]);


        $record = $request->only('files', 'checksum', 'email_number');

        $record['created_at'] = Carbon::now();
        $record['updated_at'] = Carbon::now();


        if ($validator->fails()) {

            $data['errors'] = $validator->errors();
        } else {


//            if (!@unserialize($record['data']))
//                $data['errors'] = ['wrong serialize' => 'data should be an serialized data.'];
//
//            else {
//                $data['status'] = true;
//                $data['message'] = 'Thanks Masoud. Information was received successfully.';
//                $data['data'] = Program::create($record);
//            }


            $data['status'] = true;
            $data['message'] = 'Thanks Masoud. Information was received successfully.';

            $r = Received::where('checksum', $record['checksum'])->first();
            if ($r) {
                $r->update($record);
            } else {
                Received::insert($record);
            }

            $data['data'] = Received::where('checksum', $record['checksum'])->first();


            $EmailAdapter = new EmailAdapter();
            $EmailAdapter->set($data['data']->id)->adapt()->create();


        }

        return response()
            ->json($data)
            ->withCallback($request->input('callback'));


    }

    public function index()
    {
        return Received::select('id', 'created_at', 'files')->orderBy('id', 'Desc')->get();
    }

    public function show($id)
    {
        return Received::select('id', 'created_at', 'files', 'email_number', 'checksum')->where('id', $id)->orderBy('id', 'Desc')->get();
    }


    public $current_email;
    public $need_send = FALSE;
    public $readed = FALSE;

    private function get_email($message_number = 1)
    {
        $connection = imap_open("{mail.morabiman.com:110/pop3/novalidate-cert}INBOX", "apimail@morabiman.com", "953254");
//$message_number=1;
        $structure = imap_fetchstructure($connection, $message_number);

        if (!$structure) {
            return FALSE;
        } else {
            $this->readed = true;
        }
        $attachments = array();
        if (isset($structure->parts) && count($structure->parts)) {

            for ($i = 0; $i < count($structure->parts); $i++) {


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
                if ($structure->parts[$i]->subtype == "WAV") {
//			var_dump($structure->parts[$i]);die;
                    $file_attachments[$i] = array(
                        'is_attachment' => false,
                        'filename' => '',
                        'name' => '',
                        'attachment' => ''
                    );
//				var_dump($structure->parts[$i]);die;
                    if ($structure->parts[$i]->ifdparameters) {

                        foreach ($structure->parts[$i]->dparameters as $object) {
                            if (strtolower($object->attribute) == 'filename') {
                                $file_attachments[$i]['is_attachment'] = true;
                                $file_attachments[$i]['filename'] = $object->value;
                            }
                        }
                    }

                    if ($structure->parts[$i]->ifparameters) {
                        foreach ($structure->parts[$i]->parameters as $object) {
                            if (strtolower($object->attribute) == 'name') {
                                $file_attachments[$i]['is_attachment'] = true;
                                $file_attachments[$i]['name'] = $object->value;
                            }
                        }
                    }

                    if ($file_attachments[$i]['is_attachment']) {
                        $file_attachments[$i]['attachment'] = imap_fetchbody($connection, $message_number, $i + 1);

                        if ($structure->parts[$i]->encoding == 3) { // 3 = BASE64
                            $file_attachments[$i]['attachment'] = base64_decode($file_attachments[$i]['attachment']);
                        } elseif ($structure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                            $file_attachments[$i]['attachment'] = quoted_printable_decode($file_attachments[$i]['attachment']);
                        }
                        $real_file_name = time() . ".wav";
                        $old_file_name = (strlen($file_attachments[$i]['filename']) > 0) ? $file_attachments[$i]['filename'] : $file_attachments[$i]['name'];
                        $myfile = fopen('wav_files/' . $real_file_name, "w") or die("Unable to open file!");
                        fwrite($myfile, $file_attachments[$i]['attachment']);
                        fclose($myfile);
                        $file_attachments[$i]['real_file_name'] = $real_file_name;
                        $file_attachments[$i]['old_file_name'] = $old_file_name;

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
            $h = 0;
            foreach ($file_attachments as $attachment) {
                $array['wav_files'][$h]['real_file_name'] = $attachment['real_file_name'];
                $array['wav_files'][$h]['old_file_name'] = $attachment['old_file_name'];
                $array['wav_files'][$h]['url_of_file'] = 'https://dev.morabiman.com/wav_files/' . $attachment['real_file_name'];
                $h = $h + 1;
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
//        $this->current_email = 84;

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


    public function getData()
    {

        $datas = $this->get_new_email();


        if ($this->readed) {
            echo 'email number ' . $this->current_email . ' was read' . PHP_EOL;
            if ($this->need_send) {
//    	var_dump($datas);
                $data['files'] = json_encode($datas);
                $data['checksum'] = md5(json_encode($datas));
                $data['email_number'] = $this->current_email;

//                return $data;
                $request = $this->callAPI("POST", 'https://dev.morabiman.com/api/emails/received', $data);
                die(' and sended successfully');
            } else {
                die(' and there is not any attachment so no need to send through api');
            }

        } else {
            die ('there is no new email ' . PHP_EOL . 'current email=' . $this->current_email);
        }


    }
}
