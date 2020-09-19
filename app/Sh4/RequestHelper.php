<?php

namespace App\Sh4;


use App\Helpers\Sh4Helper;
use App\Mail\SendBodyChangeToCoaches;
use App\Mail\SendRequestToCoaches;
use App\Models\Request;
use App\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendUserMail;
use PhpOffice\PhpWord\PhpWord;
use PHPRtfLite;
use Auth;
use SoapClient;


trait RequestHelper
{


    public $admin;
    public $user;
    public $coach;
    public $requestsPath = '/uploads/requests/';
    public $mediaPaths = [];

    public $request;


    public function emailHandler($request_id, $type = 'CREATE_REQUEST_IN_REQUEST', $updated = null)
    {
        $this->setRequest($request_id);

        if ($type == 'CHANGE_BODY')
            $updated = true;

        elseif ($type == 'CREATE_REQUEST_IN_REQUEST' && !in_array($this->request->payment_type, ['OTHER']))
            return 0;

        elseif ($type == 'CREATE_REQUEST_IN_PAYMENT' && !in_array($this->request->payment_type, ['CREDIT', 'ONLINE']))
            return 0;

        $this->createRtfWord($this->request, $updated);

        $this->SendEmailToCoachesAfterRequest($this->request, $updated);
//
        $this->SendEmailToUserAfterRequest($this->request, $updated);

        return 1;
    }


    private function SendEmailToUserAfterRequest($request, $updated = null)
    {
        Mail::to($request->user->email)->send(new SendUserMail($request->tracking_code, $updated));

        $this->sendSms($request->user->mobile, "jo3z3ywmyb", $request->tracking_code);
    }

    public function SendEmailToCoachesAfterRequest($request, $updated = null)
    {
        Mail::to($request->coach->email)->send(new SendRequestToCoaches($request, $updated));
//        Mail::to('sh4rifi@gmail.com')->send(new SendRequestToCoaches($request , $updated));


        Mail::to('a.babazadeh@gmail.com')->send(new SendRequestToCoaches($request, $updated));

//        foreach (User::roleIS('admin')->get() as $admin) {
//            if ($admin->email)
//                Mail::to($admin->email)->send(new SendRequestToCoaches($request, $updated));
//            if ($admin->mobile)
//                $this->sendSms($admin->mobile, "md84mht4r6", $request->tracking_code, ["coach-name" => $request->coach->first_name . "  " . $request->coach->last_name]);
//        }


        $this->sendSms($request->coach->mobile, "md84mht4r6", $request->tracking_code, ["coach-name" => $request->coach->first_name . "  " . $request->coach->last_name]);



    }


//    public function createPHPWORD($request, $body)
//    {
//        $i = 0;
//
//        $phpWord = new PhpWord();
//
//        $fontStyle = new \PhpOffice\PhpWord\Style\Font();
//        $fontStyle->setBold(true);
//
//        $section = $phpWord->addSection();
//        $section->addText("[Info]")->setFontStyle($fontStyle);
//        $section->addText("Name=" . $this->user->first_name);
//        $section->addText("Family=" . $this->user->last_name);
//        $section->addText("Acquaintance =" . $this->user->introduction_method);
//        $section->addText("Birth=" . substr(Verta($this->user->birth_day), 2, 2));
//        $section->addText("Sex=" . $this->user->gender);
//        $section->addText("Blood=" . $this->user->blood_group);
//        $section->addText("Mobile=" . $this->user->mobile);
//        $section->addText("Email=" . $this->user->email);
//        $section->addText("Coach_Fname=" . $this->coach->first_name);
//        $section->addText("Coach_Lname=" . $this->coach->last_name);
//        $section->addText("Date=" . substr(Verta(date("Y/m/d")), 2, 8));
//        $section->addText("[Body]")->setFontStyle($fontStyle);
//        $section->addText("Height=" . $body->height);
//        $section->addText("Weight=" . $body->weight);
//        $section->addText("Neck=" . $body->neck);
//        $section->addText("Chest=" . $body->chest);
//        $section->addText("Biceps=" . $body->arm_in_contraction);
//        $section->addText("Forearm=" . $body->forearm);
//        $section->addText("Wrist=" . $body->wrist);
//        $section->addText("Waist=" . $body->waist);
//        $section->addText("Hip=" . $body->hip);
//        $section->addText("Thighs=" . $body->thigh);
//        $section->addText("Calves=" . $body->shin);
//        $section->addText("Ankle=" . $body->ankle);
//
//
//        if ($request['questions']) {
//            $section->addText("[Questions]")->setFontStyle($fontStyle);
//            foreach ($request['questions'] as $question) {
//                (isset($question['excerpt']) && $question['excerpt']) ? $section->addText($question['excerpt'] . $question['question']) : $section->addText("Q" . ++$i . "=" . $question['question']);
//                $section->addText("A=" . str_replace(["\\n","\\r"], ' ', $question['answer']));
//            }
//        }
//
//        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
//        $objWriter->save(public_path() . $this->requestsPath . $request['tracking_code'] . '.rtf');
//        $this->createArchiveFile($request['tracking_code']);
//    }

    public function createRtfWord($request, $updated = null)
    {
        $rtf = new PHPRtfLite();

        $content = "[Info]" . "\n";
        $content .= "Name=" . $request->user->first_name . "\n";
        $content .= "Family=" . $request->user->last_name . "\n";
        $content .= "Acquaintance =" . $request->user->introduction_method . "\n";
        $content .= "Birth=" . substr(Verta($request->user->birth_day), 2, 2) . "\n";
        $content .= "Sex=" . __('api.genders')[$request->user->gender] . "\n";
        $content .= "Blood=" . $request->user->blood_group . "\n";
        $content .= "Mobile=" . $request->user->mobile . "\n";
        $content .= "Email=" . $request->user->email . "\n";
        $content .= "Coach_Fname=" . $request->coach->first_name . "\n";
        $content .= "Coach_Lname=" . $request->coach->last_name . "\n";
        $content .= "Date=" . substr(Verta(date("Y/m/d")), 2, 8) . "\n";
        $content .= "[Sizes]" . "\n";
        $content .= "Height=" . $request->user->body->height . "\n";
        $content .= "Weight=" . $request->user->body->weight . "\n";
        $content .= "Neck=" . $request->user->body->neck . "\n";
        $content .= "Chest=" . $request->user->body->chest . "\n";
        $content .= "Biceps=" . $request->user->body->arm_in_contraction . "\n";
        $content .= "Forearm=" . $request->user->body->forearm . "\n";
        $content .= "Wrist=" . $request->user->body->wrist . "\n";
        $content .= "Waist=" . $request->user->body->waist . "\n";
        $content .= "Hip=" . $request->user->body->hip . "\n";
        $content .= "Thighs=" . $request->user->body->thigh . "\n";
        $content .= "Calves=" . $request->user->body->shin . "\n";
        $content .= "Ankle=" . $request->user->body->ankle . "\n";
        $content .= "[Questions]" . "\n";
        if ($request->questions) {
            $i = 0;
            foreach ($request->questions as $question) {
                (isset($question['excerpt']) && $question['excerpt']) ?
                    $content .= $question['excerpt'] . "=" . Sh4Helper::convertCharsToPersian($question['answer']) . "\n" :
                    $content .= "q" . ++$i . "=" . Sh4Helper::convertCharsToPersian($question['answer']) . "\n";
            }
        }


        PHPRtfLite::registerAutoloader();
        $sect = $rtf->addSection();
        $sect->writeText($content);
        $rtf->save(public_path() . $this->requestsPath . $request->tracking_code . '.rtf');
        $this->createArchiveFile($request->tracking_code);
    }


    public function createArchiveFile($trackingCode)
    {
        $zipper = new \Chumper\Zipper\Zipper;

        if ($this->requestsPath . $trackingCode . '.rtf')
            array_push($this->mediaPaths, $this->requestsPath . $trackingCode . '.rtf');

        $requestZipped = $zipper->make(public_path() . $this->requestsPath . $trackingCode . '.zip');
        foreach ($this->mediaPaths as $file) {
            $requestZipped->add(public_path() . $file);
        }


    }


    private function setRequest($id)
    {
        $this->request = Request::where('id', $id)
            ->with([
                'coach',
                'payment' => function ($q) {
                    return $q->where('status', '<>', 'INIT');
                },
                'user' => function ($q) {
                    return $q->with('body');
                }
            ])
            ->select('*')
            ->selectRaw('total_price - (discount_percent * total_price / ?)   as price_with_discount', [100])
            ->first();
    }


    public function sendSms($mobile, $pattern_code, $code = null, $input_data = null)
    {
        $client = new SoapClient("http://188.0.240.110/class/sms/wsdlservice/server.php?wsdl");

        $user = "sms690";

        $pass = "091214354670";

        $fromNum = "10009589";

        $toNum = [$mobile];

        $input_data['tracking-code'] = $code;

        $client->sendPatternSms($fromNum, $toNum, $user, $pass, $pattern_code, $input_data);

        return 1;
    }

}
