<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function index()
    {
        return view('pages.team', [
            'teamMembers' => $this->teamMembersQuery()->get()->map(fn ($user) => $this->mapTeamMember($user))->values(),
        ]);
    }

    public function show(Request $request)
    {
        $id = (string) $request->input('id', '0');
        $member = null;
        $selectedCounterparty = null;
        $fid = Auth::user()->firma ?? session('fid', '');

        if ($id !== '0') {
            $member = $this->teamMembersQuery()->where('id', $id)->first();
            abort_unless($member, 404);
        }

        $selectedCounterpartyId = (string) old('userid', (string) ($member->userid ?? '0'));
        if ($selectedCounterpartyId !== '' && $selectedCounterpartyId !== '0') {
            $selectedCounterparty = DB::table('users')
                ->where('id', $selectedCounterpartyId)
                ->first();
        }

        $tclients = DB::table('conf')->where('type', 'tclients')->where('firma', $fid)->orderBy('name')->get();

        return view('team.show', [
            'member' => $member,
            'selectedCounterparty' => $selectedCounterparty,
            'selectedCounterpartyLabel' => $selectedCounterparty
                ? trim(implode(' ', array_filter([
                    (string) ($selectedCounterparty->orgname ?? ''),
                    trim((string) ($selectedCounterparty->secondname ?? '')),
                    trim((string) ($selectedCounterparty->name ?? '')),
                ])))
                : '',
            'tclients' => $tclients,
        ]);
    }

    public function payrollReport(Request $request)
    {
        $fid = (string) (Auth::user()->firma ?? session('fid', ''));

        $data = Report::teamPayrollLedger(
            $fid,
            (string) $request->input('date_from', ''),
            (string) $request->input('date_to', '')
        );

        return view('team.payroll_report', $data);
    }

    public function save(Request $request)
    {
        $id = (string) $request->input('id', '0');
        $fid = session('fid', '');
        $stringValue = static fn ($value): string => trim((string) ($value ?? ''));

        if ($id !== '0') {
            abort_unless($this->teamMembersQuery()->where('id', $id)->exists(), 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'secondname' => ['nullable', 'string', 'max:255'],
            'fathername' => ['nullable', 'string', 'max:255'],
            'name2' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where('firma', $fid)
                    ->ignore($id === '0' ? null : $id),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('users', 'phone')
                    ->where('firma', $fid)
                    ->ignore($id === '0' ? null : $id),
            ],
            'website' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'foto1' => ['nullable', 'string', 'max:255'],
            'foto1_file' => ['nullable', 'file', 'image', 'max:10240'],
            'status' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'orgname' => ['nullable', 'string', 'max:255'],
            'pass' => ['nullable', 'string', 'max:255'],
            'userid' => ['nullable', 'integer', 'min:0'],
        ]);

        $departmentValue = $request->filled('status') ? (int) $request->input('status') : 0;
        $currentPhoto = $stringValue($validated['foto1'] ?? ($id !== '0'
            ? DB::table('users')->where('id', $id)->value('foto1')
            : ''));

        if ($request->hasFile('foto1_file')) {
            $uploadedFile = $request->file('foto1_file');
            if ($uploadedFile && $uploadedFile->isValid()) {
                $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: $uploadedFile->extension() ?: 'jpg');
                $filename = 'team_' . ($id !== '0' ? $id : uniqid()) . '_' . now()->format('YmdHis') . '.' . $extension;
                $path = $uploadedFile->storeAs('files/team', $filename, 'public');
                $currentPhoto = $path ?: $currentPhoto;
            }
        }

        $data = [
            'name' => $stringValue($request->input('name')),
            'secondname' => $stringValue($request->input('secondname')),
            'fathername' => $stringValue($request->input('fathername')),
            'name2' => $stringValue($request->input('name2')),
            'email' => $stringValue($request->input('email')),
            'phone' => preg_replace('/\D/', '', $request->input('phone', '')),
            'website' => $stringValue($request->input('website')),
            'city' => $stringValue($request->input('city')),
            'region' => $stringValue($request->input('region')),
            'description' => $stringValue($request->input('description')),
            'foto1' => $currentPhoto,
            'orgname' => $stringValue($request->input('orgname')),
            'status' => $departmentValue,
            'userid' => (int) $request->input('userid', 0),
            'firmuser' => '1',
            'firma' => $fid,
        ];

        if ($id === '0') {
            $data['idstatus'] = 1;
            $data['ustype'] = 1;
        }

        $password = $stringValue($request->input('pass'));
        if ($password !== '') {
            $hash = Hash::make($password);
            $data['pass'] = $hash;
            $data['password'] = $hash;
        }

        $memberId = User::edit($id, $data);

        if (Schema::hasColumn('users', 'status')) {
            DB::table('users')
                ->where('id', $memberId)
                ->update(['status' => $departmentValue]);
        }

        $counterpartyId = (int) $request->input('userid', 0);
        if (
            $counterpartyId > 0
            && Schema::hasColumn('users', 'firmuser')
        ) {
            DB::table('users')
                ->where('id', $counterpartyId)
                ->where('firma', $fid)
                ->update(['firmuser' => '1']);
        }

        return redirect()->route('team.show', ['id' => $memberId])->with('success', 'Збережено');
    }

    public function destroy(Request $request)
    {
        $id = (string) $request->input('id', '0');
        $member = $this->teamMembersQuery()->where('id', $id)->first();
        abort_unless($member, 404);

        DB::table('users')->where('id', $member->id)->delete();

        return redirect()->route('team')->with('success', 'Учасника команди видалено');
    }

    private function teamMembersQuery()
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'firmuser')) {
            return DB::table('users')->whereRaw('1 = 0');
        }

        $fid = Auth::user()->firma ?? session('fid', '');

        return DB::table('users')
            ->where('firma', $fid)
            ->where('firmuser', '1')
            ->orderByDesc('top')
            ->orderBy('id');
    }

    private function mapTeamMember(object $user): object
    {
        $fullName = trim(implode(' ', array_filter([
            $user->name ?? '',
            $user->secondname ?? '',
            $user->fathername ?? '',
        ])));
        $fallbackName = trim((string) ($user->orgname ?? ''));

        return (object) [
            'id' => $user->id,
            'full_name' => $fullName !== '' ? $fullName : ($fallbackName !== '' ? $fallbackName : ('User #' . $user->id)),
            'position' => trim((string) ($user->name2 ?? '')),
            'photo' => MediaUrl::image((string) ($user->foto1 ?? '')),
            'description' => trim((string) ($user->description ?? '')),
            'location' => trim(implode(', ', array_filter([
                $user->city ?? '',
                $user->region ?? '',
            ]))),
            'email' => trim((string) ($user->email ?? '')),
            'phone' => trim((string) ($user->phone ?? '')),
            'website' => trim((string) ($user->website ?? '')),
        ];
    }
}
