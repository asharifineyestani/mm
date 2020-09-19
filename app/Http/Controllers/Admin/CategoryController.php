<?php

namespace App\Http\Controllers\Admin;

use App\Models\Study\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class CategoryController extends Controller
{
    protected function validation(Request $request)
    {
        $request->validate([
            'name' => 'required | string',
        ]);
    }

    public function index()
    {
        return view('editorArea.categories.index');
    }

	public function getCategories()
	{
		$query = Category::select('*');

		$results = datatables($query)
			->addColumn('operation', function ($category){
				return view('editorArea.categories.partials._operation', [
					'category_id' => $category->id
				]);
			})
			->rawColumns(['operation'])
			->make(true);

		return $results;
    }

    public function create()
    {
        return view('editorArea.categories.create');
    }

    public function store(Request $request)
    {
        $this->validation($request);
        if (Category::create($request->all())){
            Session::flash('alert-info', 'success,' . __('mm.popup.add.success', ['name' => __('mm.public.category',['name'=> __('study.sectionName')])]));
            return redirect()->route('admin.categories.index');
        }
        Session::flash('alert-info', 'error,' . __('mm.popup.add.error', ['name' => __('mm.public.category',['name'=> __('study.sectionName')])]));
        return redirect()->back();
    }

    public function show($id)
    {
        return view('editorArea.components.editor-frontend');
    }

    public function edit($id)
    {
        $category = Category::find($id);
        return view('editorArea.categories.update',compact('category'));
    }

    public function update(Request $request, $id)
    {
        $this->validation($request);
        if (Category::find($id)->update($request->all())){
            Session::flash('alert-info', 'success,' . __('mm.popup.update.success', ['name' => __('mm.public.category',['name'=> __('study.sectionName')])]));
            return redirect()->back();
        }
        Session::flash('alert-info', 'error,' . __('mm.popup.update.error', ['name' => __('mm.public.category',['name'=> __('study.sectionName')])]));
        return redirect()->back();

    }

    public function destroy($id)
    {
        if (Category::destroy($id)) {
            die(true);
        }
        die(false);
    }
}
