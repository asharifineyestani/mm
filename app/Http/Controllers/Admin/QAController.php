<?php

namespace App\Http\Controllers\Admin;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class QAController extends Controller
{
    public function validation(Request $request)
    {
        $request->validate([
            'title' => 'required ',
        ]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $questions = Question::all();
        return view('admin.questions.index',compact('questions'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.QA.single');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $this->validation($request);
        $question = Question::create($request->all());
        if ($question){
            if ($request->has('answers')){
                $answers = [];
                for ($i = 1 ; $i < count($request->input('answers'))+1;$i++){
                    array_push($answers,[
                        'question_id'=> $question->id,
                        'body' => $request->input('answers')[$i],
                        'order' => $request->input('orders')[$i]
                ]);
                }
                $added_answers = Answer::insert($answers);
            }
            Session::flash('alert-info', 'success,'.__('mm.popup.add.success',['name'=>__('mm.QA.question')]));
            return redirect()->route('admin.QA.index');
        }
        Session::flash('alert-info', 'error,'.__('mm.popup.add.error',['name'=>__('mm.QA.question')]));
        return redirect()->route('admin.QA.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $question = Question::find($id);
        return view('admin.QA.single',compact('question'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //todo: remove all edit this
        //dd($request->all());
        $this->validation($request);
        $question = Question::find($id)->update($request->all());
        if ($question){
            if ($request->has('answers')){
                $remove_past_answers = Answer::where('question_id',$id)->delete();
                if ($remove_past_answers){
                    $answers = [];
                    for ($i = 1 ; $i < count($request->input('answers'))+1;$i++){
                        array_push($answers,[
                            'question_id'=> $id,
                            'body' => $request->input('answers')[$i],
                            'order' => $request->input('orders')[$i]
                        ]);
                    }
                    Answer::insert($answers);
                }
            }
            Session::flash('alert-info', 'success,'.__('mm.popup.update.success',['name'=>__('mm.QA.question')]));
            return redirect()->route('admin.QA.index');
        }
        Session::flash('alert-info', 'error,'.__('mm.popup.update.error',['name'=>__('mm.QA.question')]));
        return redirect()->route('admin.QA.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (Question::destroy($id)){
            die(true);
        }
        die(false);
    }
}
