<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Artist;
use App\Models\Work;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreWorkRequest;
use App\Http\Requests\UpdateWorkRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;


class WorkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() // non posso usare la dependency injection di Artist, ma solo di Work
    {
        $user = Auth::user();
        $artist = Artist::where('user_id', $user->id)->first();
        $works = Work::where('artist_id', $artist->id)->get();
        // dd($works);
        return view('admin.works.index', compact('artist', 'user', 'works'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {   
        $user = Auth::user();
        $artist = Artist::where('user_id', $user->id)->first();
        $categories = Category::all();
        return view('admin.works.create', compact('artist', 'user', 'categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWorkRequest $request, Work $work)
    {
        $user = Auth::user();
        $artist = Artist::where('user_id', $user->id)->first();
        $validated_data = $request->validated();
        $validated_data['artist_id'] = $artist->id;
        $validated_data['slug'] = Str::slug($validated_data['title'], '-') . $user->id;

        // Verifica se esiste già un'opera con lo stesso titolo per l'artista
        if ($artist->works()->where('title', $validated_data['title'])->exists()) {
            return redirect()->route('admin.works.create')
                ->with('error', 'Hai già un\'opera con lo stesso titolo.');
        }

        if ($request->hasFile('image')) {
            $path = Storage::put('work_image', $request->image);
            $validated_data['image'] = $path;
        }

        $newWork = Work::create($validated_data);

        //dd($newWork);
        if ($request->has('categories')) {
            $newWork->categories()->attach($request->categories);
        }
    
        return redirect()->route('admin.works.show', $newWork->slug)
            ->with('success', 'Opera creata con successo.');
        
    }

    /**
     * Display the specified resource.
     */
    public function show(Work $work)
    {
        // info dell'artista nella show
        $user = Auth::user();
        $artist = Artist::where('user_id', $user->id)->first();
        return view('admin.works.show', compact('work', 'artist', 'user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Work $work)
    {
        $user = Auth::user();
        $artist = Artist::where('user_id', $user->id)->first();
        $categories = Category::all();
        return view('admin.works.edit', compact('work', 'categories', 'user', 'artist'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWorkRequest $request, Work $work)
    {
        $user = Auth::user();
        $artist = Artist::where('user_id', $user->id)->first();
        $validated_data = $request->validated();
        $validated_data['artist_id'] = $artist->id;
        $validated_data['slug'] = Str::slug($validated_data['title'], '-') . $user->id;

        // Se è presente un nuovo file immagine, aggiornalo
        if ($request->hasFile('image')) {
            // Aggiorna l'immagine e altri campi se necessario
            return redirect()->route('admin.works.index')
        ->with('error', 'Non puoi cambiare l\'immagine, crea una nuova opera.');
        }

        $work->update($validated_data);

        //dd($newWork);
        if ($request->has('categories')) {
            $work->categories()->sync($request->categories);
        }
    
        return redirect()->route('admin.works.show', $work->slug)
            ->with('success', 'Opera aggiornata con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Work $work)
    {
        if ($work->image) {
            Storage::delete($work->image);
        }
        $work->delete();
        return redirect()->route('admin.works.index')
        ->with('success', 'Opera eliminata con successo.');;
    }
}
