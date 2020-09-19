<?php

namespace App\Sh4;


use App\Mail\SendRequestToCoaches;
use App\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendUserMail;
use PhpOffice\PhpWord\PhpWord;
use PHPRtfLite;
use Auth;


trait SendEmail
{


    public $admin;
    public $user;
    public $coach;
    public $requestsPath = '/uploads/requests/';
    public $mediaPaths;


    public function sendEmail($data)
    {
        Mail::to('sh4rifi@gmail.com')->send(new SendRequestToCoaches($data));
        Mail::to('sh4rifi@gmail.com')->send(new SendUserMail($data['tracking_code']));


//        Mail::to($this->user->email)->send(new SendUserMail($data['tracking_code']));
//        Mail::to($this->coach->email)->send(new SendRequestToCoaches($data));
//        Mail::to($this->admin->email)->send(new SendRequestToCoaches($data));
//        Mail::to('a.babazadeh@gmail.com')->send(new SendRequestToCoaches($data));
    }


    public function whoIsAdmin()
    {
        $result = User::select('users.id', 'users.email', 'users.birth_day', 'users.first_name', 'users.last_name', 'users.avatar', 'users.status')
            ->roleIS('admin')->first();
        return $result;
    }


    public function createRtfWord2($request, $body)
    {
        $i = 0;

        $phpWord = new PhpWord();

        $fontStyle = new \PhpOffice\PhpWord\Style\Font();
        $fontStyle->setBold(true);

        $section = $phpWord->addSection();
        $section->addText("[Info]")->setFontStyle($fontStyle);
        $section->addText("Name=" . $this->user->first_name);
        $section->addText("Family=" . $this->user->last_name);
        $section->addText("Acquaintance =" . $this->user->introduction_method);
        $section->addText("Birth=" . substr(Verta($this->user->birth_day), 2, 2));
        $section->addText("Sex=" . $this->user->gender);
        $section->addText("Blood=" . $this->user->blood_group);
        $section->addText("Mobile=" . $this->user->mobile);
        $section->addText("Email=" . $this->user->email);
        $section->addText("Coach_Fname=" . $this->coach->first_name);
        $section->addText("Coach_Lname=" . $this->coach->last_name);
        $section->addText("Date=" . substr(Verta(date("Y/m/d")), 2, 8));
        $section->addText("[Body]")->setFontStyle($fontStyle);
        $section->addText("Height=" . $body->height);
        $section->addText("Weight=" . $body->weight);
        $section->addText("Neck=" . $body->neck);
        $section->addText("Chest=" . $body->chest);
        $section->addText("Biceps=" . $body->arm_in_contraction);
        $section->addText("Forearm=" . $body->forearm);
        $section->addText("Wrist=" . $body->wrist);
        $section->addText("Waist=" . $body->waist);
        $section->addText("Hip=" . $body->hip);
        $section->addText("Thighs=" . $body->thigh);
        $section->addText("Calves=" . $body->shin);
        $section->addText("Ankle=" . $body->ankle);


        if ($request['questions']) {
            $section->addText("[Questions]")->setFontStyle($fontStyle);
            foreach ($request['questions'] as $question) {
                (isset($question['excerpt']) && $question['excerpt']) ? $section->addText($question['excerpt'] . $question['question']) : $section->addText("Q" . ++$i . "=" . $question['question']);
                $section->addText("A=" . $question['answer']);
            }
        }

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save(public_path() . $this->requestsPath . $request['tracking_code'] . '.rtf');
        $this->createArchiveFile($request['tracking_code']);
    }

    public function createRtfWord($request, $body)
    {
        $rtf = new PHPRtfLite();

        $content = "[Info]" . "\n";
        $content .= "Name=" . $this->user->first_name . "\n";
        $content .= "Family=" . $this->user->last_name . "\n";
        $content .= "Acquaintance =" . $this->user->introduction_method . "\n";
        $content .= "Birth=" . substr(Verta($this->user->birth_day), 2, 2) . "\n";
        $content .= "Sex=" . $this->user->gender . "\n";
        $content .= "Blood=" . $this->user->blood_group . "\n";
        $content .= "Mobile=" . $this->user->mobile . "\n";
        $content .= "Email=" . $this->user->email . "\n";
        $content .= "Coach_Fname=" . $this->coach->first_name . "\n";
        $content .= "Coach_Lname=" . $this->coach->last_name . "\n";
        $content .= "Date=" . substr(Verta(date("Y/m/d")), 2, 8) . "\n";
        $content .= "[Sizes]" . "\n";
        $content .= "Height=" . $body->height . "\n";
        $content .= "Weight=" . $body->weight . "\n";
        $content .= "Neck=" . $body->neck . "\n";
        $content .= "Chest=" . $body->chest . "\n";
        $content .= "Biceps=" . $body->arm_in_contraction . "\n";
        $content .= "Forearm=" . $body->forearm . "\n";
        $content .= "Wrist=" . $body->wrist . "\n";
        $content .= "Waist=" . $body->waist . "\n";
        $content .= "Hip=" . $body->hip . "\n";
        $content .= "Thighs=" . $body->thigh . "\n";
        $content .= "Calves=" . $body->shin . "\n";
        $content .= "Ankle=" . $body->ankle . "\n";
        $content .= "[Questions]" . "\n";
        if ($request['questions']) {
            $i = 0;
            foreach ($request['questions'] as $question) {
                (isset($question['excerpt']) && $question['excerpt']) ?
                    $content .= $question['excerpt'] . "=" . $question['answer'] . "\n" :
                    $content .= "q" . ++$i . "=" . $question['answer'] . "\n";
            }
        }


        PHPRtfLite::registerAutoloader();
        $sect = $rtf->addSection();
        $sect->writeText($content);
        $rtf->save(public_path() . $this->requestsPath . $request->tracking_code . '.rtf');
        $this->createArchiveFile($request['tracking_code']);
    }


    public function createArchiveFile($trackingCode)
    {
        $zipper = new \Chumper\Zipper\Zipper;
        array_push($this->mediaPaths, $this->requestsPath . $trackingCode . '.rtf');

        $requestZipped = $zipper->make(public_path() . $this->requestsPath . $trackingCode . '.zip');
        foreach ($this->mediaPaths as $file) {
            $requestZipped->add(public_path() . $file);
        }

    }

}
