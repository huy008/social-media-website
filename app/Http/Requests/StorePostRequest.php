<?php

namespace App\Http\Requests;

use App\Models\GroupUser;
use App\Http\Enums\GroupUserStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\File;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
     /**
      * Determine if the user is authorized to make this request.
      */
     public function authorize(): bool
     {
          return true;
     }

     /**
      * Get the validation rules that apply to the request.
      *
      * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
      */
     public function rules(): array
     {
          return [
               'body' => ['nullable', 'string'],
               'attachments' => 'array|max:50',
               'attachments.*' => [
                    'file',
                    File::types([
                         'jpg',
                         'jpeg',
                         'png',
                         'gif',
                         'webp',
                         'mp3',
                         'wav',
                         'mp4',
                         "doc",
                         "docx",
                         "pdf",
                         "csv",
                         "xls",
                         "xlsx",
                         "zip"
                    ])->max(500 * 1024 * 1024)
               ],
               'user_id' => ['numeric'],
               'group_id' => ['nullable', 'exists:groups,id', function ($attribute, $value, \Closure $fail) {
                    $groupUser = GroupUser::where('user_id', Auth::id())
                         ->where('group_id', $value)
                         ->where('status', GroupUserStatus::APPROVED->value)
                         ->exists();

                    if (!$groupUser) {
                         $fail('You don\'t have permission to create post in this group');
                    }
               }]
          ];
     }

     protected function prepareForValidation()
     {
          $this->merge([
               'user_id' => auth()->user()->id,

               'body' => $this->input('body') ?: ''
          ]);
     }

     protected function passedValidation(): void
     {
          $data = $this->validated();
          $data['about'] = nl2br($data['about']);
          $this->replace($data);
     }
}
