<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function update(Request $request, ContactMessage $contactMessage)
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,read,closed'],
        ]);

        $data['read_at'] = $data['status'] === 'new'
            ? null
            : ($contactMessage->read_at ?: now());
        $contactMessage->update($data);

        return back()->with('success', 'Murojaat holati yangilandi.');
    }

    public function destroy(ContactMessage $contactMessage)
    {
        $contactMessage->delete();

        return back()->with('success', 'Murojaat o‘chirildi.');
    }
}
