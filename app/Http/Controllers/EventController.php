<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Event;
use App\Models\User;

class EventController extends Controller
{
    public function index() {

        $search = request('search');

        if($search){
            $events = Event::where([
                ['title', 'like', '%'.$search.'%']
            ])->get();
        } else{
            $events = Event::all();
        }
        

        return view('welcome', [ 'events' => $events, 'search' => $search ]);
    }

    public function create(){
        return view('events.create');
    }

    public function store(Request $request){

        $event = new Event();

        $event->title = $request->title;
        $event->date = $request->date;
        $event->city = $request->city;
        $event->description = $request->description;
        $event->private = $request->private;
        $event->items = $request->items;

        // Image upload

        if($request->hasFile('image') && $request->file('image')->isValid() ){

            $requestImage = $request->image;

            $extension = $requestImage->extension();

            $imageName = md5($requestImage->getClientOriginalName() . strtotime("now")) . "." . $extension;

            $requestImage->move(public_path('img/events'), $imageName);

            $event->image = $imageName;

        }

        $user = Auth::user();

        $event->user_id = $user->id;

        $event->save();

        return redirect('/')->with("msg", "Evento criado com sucesso!");

    }

    public function show($id){

        $user = Auth::user();
        $hasUserJoined = false;

        if($user){
            $userEvents = $user->eventsAsParticipant->toArray();

            foreach($userEvents as $userEvent){
                if($userEvent['id'] == $id){
                    $hasUserJoined = true;
                }
            }
        }

        $event = Event::findOrFail($id);

        $eventOwner = User::where('id', $event->user_id)->first()->toArray();

        return view('events.show', [ 'event' => $event, 'eventOwner' => $eventOwner, 'hasUserJoined' => $hasUserJoined, 'user' => $user ]);

    }

    public function dashboard(){
        $user = Auth::user();

        $events = $user->events;

        $eventsAsParticipant = $user->eventsAsParticipant;

        return view('events.dashboard', ['events' => $events, 'eventsAsParticipant' => $eventsAsParticipant]);
    }

    public function destroy($id){

        $user = Auth::user();

        $event = Event::findOrFail($id);

        if($user->id !== $event->user->id){
            return redirect('/dashboard');
        }

        $event->delete();

        return redirect('/dashboard')->with('msg', 'Evento excluído com sucesso!');

    }

    public function edit($id){

        $user = Auth::user();

        $event = Event::findOrFail($id);

        if($user->id !== $event->user->id){
            return redirect('/dashboard');
        }

        return view('events.edit', [ 'event' => $event ]);

    }

    public function update(Request $request){

        $data = $request->all();

        if($request->hasFile('image') && $request->file('image')->isValid() ){

            $requestImage = $request->image;

            $extension = $requestImage->extension();

            $imageName = md5($requestImage->getClientOriginalName() . strtotime("now")) . "." . $extension;

            $requestImage->move(public_path('img/events'), $imageName);

            $data['image'] = $imageName;

        }

        Event::findOrFail($request->id)->update($data);

        return redirect('/dashboard')->with('msg', 'Evento editado com sucesso!');
    }

    public function joinEvent($id){

        $user = Auth::user();

        $user->eventsAsParticipant()->attach($id);

        $event = Event::findOrFail($id);

        return redirect('/dashboard')->with('msg', 'Sua presença está confirmada no evento '.$event->title);

    }

    public function leaveEvent($id){

        $user = Auth::user();

        $user->eventsAsParticipant()->detach($id);

        $event = Event::findOrFail($id);

        return redirect('/dashboard')->with('msg', 'Você saiu com sucesso do evento '.$event->title);

    }
}
