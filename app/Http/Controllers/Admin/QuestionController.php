<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Http\Requests\QuestionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class QuestionController extends Controller
{
    public function index()
    {
        $questions = Question::all();
        return view('admin.questions.index', compact('questions'));
    }

    public function edit($id)
    {
        $question = Question::find($id);
        return view('admin.questions.edit', compact('question'));
    }

    public function create()
    {
        return view('admin.questions.create');
    }


    public function store(QuestionRequest $request)
    {
        $question = Question::find(Question::insertGetId($request->only('title')));


        if ($request->has('answers'))
            $question->answers()->createMany($request->input('answers'));


        Session::flash('alert-info', 'success,' . __('mm.popup.add.success', ['name' => __('mm.question.singular')]));
        return redirect()->route('admin.QA.index');
    }


    public function update(QuestionRequest $request, $id)
    {
        $question = Question::find($id);

        $question->update($request->only('title'));

        $question->answers()->delete();


        if ($request->has('answers'))
            $question->answers()->createMany($request->input('answers'));


        Session::flash('alert-info', 'success,' . __('mm.popup.update.success', ['name' => __('mm.QA.question')]));
        return redirect()->route('admin.QA.index');
    }


    public function destroy($id)
    {
        if (Question::destroy($id)) {
            die(true);
        }
        die(false);
    }


}
