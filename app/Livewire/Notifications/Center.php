<?php
namespace App\Livewire\Notifications;
use Livewire\Component;
class Center extends Component { public function markRead(string $id):void{$n=auth()->user()->notifications()->whereKey($id)->firstOrFail();$n->markAsRead();} public function markAll():void{auth()->user()->unreadNotifications->markAsRead();} public function render(){return view('livewire.notifications.center',['notifications'=>auth()->user()->notifications()->latest()->limit(100)->get()]);} }
