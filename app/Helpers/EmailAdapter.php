<?php


namespace App\Helpers;

use App\Models\Adapted;
use App\Models\Email;
use App\Models\Program;
use App\Models\Received;
use App\Models\Study\Workout;
use App\User;


/*
 *
 * */

class EmailAdapter
{

    private $daysPersonMustDoWorkout = [];

    private $types = [
        //$anaerobics
        'Static' => "ثابت",
        'Increasing' => "افزايشي",
        'Decreasing' => "کاهشي",
        'Super with next' => "سوپر با حرکت بعد",
        'Super with previous' => "سوپر با حرکت قبل",
        'Drop Set' => "دراپ ست",
        'Pyramid' => "هرمي",
        'Maximal Effort' => "حداکثر فشار",
        'Dynamic Effort' => "پويا",
        'Pre-exhaust with next' => "پيش خستگي با بعدي",
        'Pre-exhaust with previous' => "پيش خستگي با قبلي",
        'Post-exhaust with next' => "پس خستگي با بعدي",
        'Post-exhaust with previous' => "پس خستگي با قبلي",
        '21s' => "21",
        'Rest Pause' => "رست پاز",
        'Cheating' => "تقلب",
        'Super Slow' => "فوق آهسته",
        'Decussate with next' => "يکي در ميان با بعدي",
        'Decussate with previous' => "يکي در ميان با قبلي",
        'Negative' => "منفي",
        'Circuit' => "گردشي",
        'Breathing Ladder' => "پلکان تنفس",
        'GVT' => "حجم آلماني",
        'Tabata' => "تاباتا",
        'Cluster' => "کلاستر",
        'FST7' => "FST7",
        'Tri-set' => "تراي ست",
        'Giant-set' => "جاينت ست",
        //$aerobics
        'Track' => "ثابت",
        'Interval' => "متناوب",
        'Weight Loss' => "کاهش وزن",
        'Cardio' => "قلبي عروقي",
        '5K' => "5K",
        '10K' => "10K",
        'Custom Heart Rate' => "ضربان قلب",
        'Calories' => "کالري هدف",
        'Distance' => "مسافت هدف",
        'Time' => "زمان هدف",
        'Glute' => "گلوت",
    ];


    private $id;

    private $receivedFiles;

    private $weekDays;

    private $workoutDays;

    private $adaptedEmail;


    private $loggedEmail;


    /**
     * @var integer
     *
     * how many workout file are there?
     */
    private $files_qtv;
    /**
     * @var array
     */
    private $testValue;


    public function __construct()
    {
        $this->weekDays = [
            'Saturday',
            'Sunday',
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday'
        ];

    }


    public function test()
    {
        return $this->daysPersonMustDoWorkout;
    }

    public function updateAll()
    {
        $emails = Email::all();

        foreach ($emails as $email) {

            $log = Program::find($email->log_id);

            if ($log) {
                $this->set($log->id)->adapt()->create();
            }
        }

        return 'all records updated';
    }

    public function set($id)
    {
        $this->loggedEmail = Program::find($id);
        $this->receivedFiles = Program::find($id)->data;

        return $this;

    }


    public function create()
    {
        $adaptedEmail = $this->adaptedEmail;


        $adaptedEmail['checksum'] = $this->loggedEmail->checksum;
        $adaptedEmail['log_id'] = $this->loggedEmail->id;

        $email = Adapted::where('checksum', $adaptedEmail['checksum'])->first();


        $adaptedEmail = $this->convertCharsToPersian($adaptedEmail);


        if ($email) {
            Adapted::find($email->id)->update($adaptedEmail);
        } else {
            Adapted::create($adaptedEmail);
        }

    }


    public static function convertCharsToPersian($string)
    {
        $persian = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
        $arabic = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩‎', 'ك', 'ي');
        $standard = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'ک', 'ی');
        $new_string = str_replace($persian, $standard, str_replace($arabic, $standard, $string));
        return $new_string;
    }

    public function adapt()
    {
        $this->setUser();
        $this->setWorkout();
        $this->setNutrition();
        $this->setAnalyse();
        $this->adaptMeals();
        $this->setWave();

        return $this;
    }


    public function get()
    {
        return $this->adaptedEmail;
    }


    private function setAnalyse()
    {
        $pattern = '/^Analyze.*\.ini$/';

        $key = $this->getKeyByPattern($pattern);

        $this->adaptedEmail['analyze'] = ($key) ? $this->receivedFiles[$key[0]] : null;

    }


    private function setWave()
    {
        if (isset($this->receivedFiles['wav_files']))
            $this->adaptedEmail['sounds'] = $this->receivedFiles['wav_files'];

    }


    private function setNutrition()
    {
        $pattern = '/^Nutrition.*\.ini$/';

        $key = $this->getKeyByPattern($pattern);

        $this->adaptedEmail['nutrition'] = ($key) ? $this->receivedFiles[$key[0]] : null;
    }


    private function adaptMeals()
    {
        $i = 0;

        $adaptedMeals = [];

        $nutrition = $this->adaptedEmail['nutrition'];

        if (!$nutrition)
            return null;

        $keys = $this->getKeyByPattern('/^Meal.*/', $nutrition);


        foreach ($keys as $key) {
            $adaptedMeals[++$i] = $this->adaptMeal($this->adaptedEmail['nutrition'][$key]);
            unset($this->adaptedEmail['nutrition'][$key]);
        }


        $this->adaptedEmail['nutrition']['Meals'] = $adaptedMeals;


    }

    private function adaptWorkout($workouts)
    {
        $adaptedWorkouts = [];


        $adapted = null;

        $i = 0;
        foreach ($workouts as $key => $value) {
            $row = Workout::select('id', 'name as name_fa')->where('en_name', $key)->with('addables')->first();


            $adapted[$i] = $value;
            $adapted[$i]['name'] = $key;

            if (isset($adapted[$i]['Type']))
                $adapted[$i]['Type'] = $this->types[$adapted[$i]['Type']];

            if ($row)
                $adapted[$i] = array_merge($adapted[$i], $row->toArray());


            if (isset($adapted[$i]['Total_Time']) && $adapted[$i]['Total_Time'] == null)
                unset($adapted[$i]['Total_Time']);

            $i++;


            //            $adaptedWorkout = $this->attachFile($adaptedWorkout);

            /*__________________________________________________________________________________________________________________________________________Added 19 Dec end*/

            //            if ($adapted)
            //                $adaptedWorkouts[] = array_merge($adapted, $workouts[$key]);


        }

        return ($adapted);
    }


    private function attachFile($workout)
    {
        //        if (isset($workout['Link'])) {
        //            $link = $workout['Link'];
        //            preg_match('/.*show\/(.*)/', $link, $matches);
        //            $id = $matches[1];
        //            $row = Workout::select('id')->where('id', $id)->with('addables')->first()->toArray();
        //            return array_merge($workout, $row);
        //        }


        $row = Workout::select('id', 'name as name_fa')->where('en_name', $workout['name'])->with('addables')->first();

        $row = $row ? $row->toArray() : [];

        return array_merge($workout, $row);

    }


    private function adaptMeal($meals)
    {
        $i = 0;
        $adaptedMeal = [];
        $convertList = [
            'Time' => 'زمان مصرف',
            'Title' => 'عنوان',
        ];

        foreach ($meals as $key => $value) {

            //            $adaptedMeal[$i]['key'] =  in_array($key, ['Title', 'Time']) ? $convertList[$key] : $key;
            //            $adaptedMeal[$i]['value'] = $value;


            if (in_array($key, ['Title', 'Time'])) {
                if ($key == 'Title') $adaptedMeal['title'] = $value;
                if ($key == 'Time') $adaptedMeal['time'] = $value;
            } else {
                $adaptedMeal['items'][$i]['key'] = $key;
                $adaptedMeal['items'][$i]['value'] = $value;
            }

            $i++;
        }

        return $adaptedMeal;
    }

    private function setUser()
    {
        if (!isset($this->receivedFiles['Tag.ini']))
            return false;

        $email = $this->receivedFiles['Tag.ini']['User']['Email'];

        $email = $this->trim($email);

        $user = User::where('email', $email)->first();

        $this->adaptedEmail['user_id'] = $user ? $user->id : null;


    }


    private function getKeyByPattern($pattern, $array = null)
    {
        $array = ($array) ? $array : $this->receivedFiles;

        $receivedEmailKeys = array_keys($array);

        $keysWithPattern = preg_grep($pattern, $receivedEmailKeys);

        return array_values($keysWithPattern);
    }


    private function setWorkout()
    {

        $filesName = $this->getKeyByPattern('/^[1-7]{1}\.ini$/');

        $this->files_qtv = (is_array($filesName) && count($filesName)) ? count($filesName) : 0;

        $this->setDaysPersonMustDoWorkout();


        $commentkey = $this->getKeyByPattern('/^Comments.*\.ini$/');

        if ($commentkey or $this->files_qtv == 0)
            $this->setWorkoutDays();

        $workoutWeekDays = [];

        foreach ($this->weekDays as $key => $day)
            $weekDays[$day] = null;


        if (!$filesName) {
            $this->adaptedEmail['workout'] = null;
            return null;
        }


        foreach ($filesName as $key => $fileName) {

            $workoutWeekDays[$this->workoutDays[$key]] = $this->adaptWorkout($this->receivedFiles[$fileName]);

        }


        $orderedWorkoutWeekDays = array_merge(array_flip($this->weekDays), $workoutWeekDays);

        $orderedWorkoutWeekDays = array_map([$this, "adaptValue"], $orderedWorkoutWeekDays);

        $this->adaptedEmail['workout'] = $orderedWorkoutWeekDays ? $orderedWorkoutWeekDays : null;


        $this->adaptedEmail['workout']['description'] = ($commentkey && $orderedWorkoutWeekDays) ? $this->receivedFiles['Comments.ini']['Comments']['Text'] : null;
        $this->adaptedEmail['workout']['description'] = preg_replace("/\|/", "<br/>", $this->adaptedEmail['workout']['description']);

    }


    private function adaptValue($value)
    {
        if (is_numeric($value))
            return null;
        else
            return $value;
    }


    private function setWorkoutDays($showAsWeekDay = true)
    {
        $adaptedWorkoutDays = [];

        $workoutDays = $this->daysPersonMustDoWorkout;

        foreach ($workoutDays as $key => $day) {
            $day = $this->trim($day);
            if ($day == "true")
                $adaptedWorkoutDays[] = ($showAsWeekDay) ? $this->getWeekDay($key) : $key;
            $this->workoutDays = $adaptedWorkoutDays ? $adaptedWorkoutDays : null;
        }


    }


    private function setDaysPersonMustDoWorkout()
    {
        $days = [
            "Day1" => "false",
            "Day2" => "false",
            "Day3" => "false",
            "Day4" => "false",
            "Day5" => "false",
            "Day6" => "false",
            "Day6" => "false",
            "Day7" => "false",
        ];


        switch ($this->files_qtv) {
            case 2:
                $days['Day1'] = "true";
                $days['Day4'] = "true";
                break;
            case 3:
                $days['Day1'] = "true";
                $days['Day3'] = "true";
                $days['Day5'] = "true";
                break;
            case 4:
                $days['Day1'] = "true";
                $days['Day2'] = "true";
                $days['Day4'] = "true";
                $days['Day5'] = "true";
                break;
            case 5:
                $days['Day1'] = "true";
                $days['Day2'] = "true";
                $days['Day3'] = "true";
                $days['Day5'] = "true";
                $days['Day6'] = "true";
                break;
            case 6:
                $days['Day1'] = "true";
                $days['Day2'] = "true";
                $days['Day3'] = "true";
                $days['Day4'] = "true";
                $days['Day5'] = "true";
                $days['Day6'] = "true";
                break;
            case 7:
                $days['Day1'] = "true";
                $days['Day2'] = "true";
                $days['Day3'] = "true";
                $days['Day4'] = "true";
                $days['Day5'] = "true";
                $days['Day6'] = "true";
                $days['Day7'] = "true";
                break;
        }


        $this->daysPersonMustDoWorkout = $days;

        if (
            isset($this->receivedFiles['Comments.ini']['Week']) &&
            is_array($this->receivedFiles['Comments.ini']['Week']) &&
            count($this->receivedFiles['Comments.ini']['Week']) > 0 &&
            $this->files_qtv == $this->trueDaysQtv()
        ) {

            $this->daysPersonMustDoWorkout = $this->receivedFiles['Comments.ini']['Week'];
        }


    }

    private function trueDaysQtv()
    {
        $qtv = 0;
        foreach ($this->receivedFiles['Comments.ini']['Week'] as $key => $value)
            if ($value === "true" or $value === "True" or $value === true)
                $qtv += 1;

        return $qtv;

    }

    private function getWeekDay($string, $pattern = '/([0-9])/')
    {
        preg_match($pattern, $string, $matches);
        $key = (int)$matches[0] - 1;
        return $this->weekDays[$key];
    }


    private function trim($string)
    {
        $string = preg_replace('/(\v|\s)+/', ' ', $string);
        $string = trim($string);
        return $string;
    }


}
