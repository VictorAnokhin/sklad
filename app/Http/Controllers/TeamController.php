<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use App\Support\HoldingScope;
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
        $fid = $this->activeFid();

        return view('pages.team', [
            'teamMembers' => $this->teamMembersQuery()->get()->map(fn ($user) => $this->mapTeamMember($user))->values(),
            'companyOptions' => $this->companyOptions($fid),
            'selectedCompanyIds' => [(string) $fid],
        ]);
    }

    public function show(Request $request)
    {
        $id = (string) $request->input('id', '0');
        $member = null;
        $fid = $this->activeFid();

        if ($id !== '0') {
            $member = $this->teamMembersQuery()->where('u.id', $id)->first();
            abort_unless($member, 404);
        }

        return view('team.show', [
            'member' => $member,
            'companyOptions' => $this->companyOptions($fid),
            'selectedCompanyIds' => $member
                ? $this->membershipProjectIds((int) $member->id, HoldingScope::projectIdsFor($fid))
                : [(string) $fid],
        ]);
    }

    public function users(Request $request)
    {
        $fid = $this->activeFid();
        $firmaScope = HoldingScope::projectIdsFor($fid);
        $searchValue = $request->query('search', '');
        $search = is_string($searchValue) ? trim($searchValue) : '';

        $query = DB::table('users as u')
            ->whereIn('u.firma', $firmaScope)
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%' . mb_substr($search, 0, 100) . '%';
                $query->where(function ($query) use ($term): void {
                    $query->where('u.name', 'like', $term)
                        ->orWhere('u.secondname', 'like', $term)
                        ->orWhere('u.orgname', 'like', $term)
                        ->orWhere('u.email', 'like', $term)
                        ->orWhere('u.phone', 'like', $term);
                });
            })
            ->orderBy('u.secondname')
            ->orderBy('u.name')
            ->orderBy('u.id');

        $users = $query->paginate(10, [
            'u.id',
            'u.name',
            'u.secondname',
            'u.fathername',
            'u.orgname',
            'u.email',
            'u.phone',
            'u.firma',
        ]);

        $membershipMap = collect();
        if (Schema::hasTable('team_memberships')) {
            $membershipMap = DB::table('team_memberships')
                ->whereIn('user_id', $users->getCollection()->pluck('id'))
                ->whereIn('project_id', $firmaScope)
                ->get(['user_id', 'project_id'])
                ->groupBy('user_id');
        }

        $companyNames = $this->companyOptions($fid)->pluck('name', 'id');
        $users->getCollection()->transform(function (object $user) use ($membershipMap, $companyNames): array {
            $projectIds = collect($membershipMap->get($user->id, []))
                ->pluck('project_id')
                ->map(fn ($id): string => (string) $id)
                ->values();

            return [
                'id' => (int) $user->id,
                'name' => $this->userDisplayName($user),
                'email' => trim((string) ($user->email ?? '')),
                'phone' => trim((string) ($user->phone ?? '')),
                'firma' => (string) ($user->firma ?? ''),
                'project_ids' => $projectIds->all(),
                'company_names' => $projectIds->map(fn (string $id): string => (string) ($companyNames[$id] ?? ''))->filter()->values()->all(),
            ];
        });

        return response()->json($users);
    }

    public function attach(Request $request)
    {
        $fid = $this->activeFid();
        $firmaScope = HoldingScope::projectIdsFor($fid);
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'project_ids' => ['required', 'array', 'min:1'],
            'project_ids.*' => ['integer', Rule::in($firmaScope)],
        ]);

        $user = DB::table('users')
            ->where('id', $validated['user_id'])
            ->whereIn('firma', $firmaScope)
            ->first();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Пользователь не найден в текущей компании или холдинге.'], 404);
        }

        if (! Schema::hasTable('team_memberships')) {
            return response()->json(['success' => false, 'message' => 'Сначала выполните миграции базы данных.'], 503);
        }

        $this->addMemberships((int) $user->id, $validated['project_ids']);

        return response()->json(['success' => true]);
    }

    public function payrollReport(Request $request)
    {
        $fid = $this->activeFid();

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
        $fid = $this->activeFid();
        $stringValue = static fn ($value): string => trim((string) ($value ?? ''));

        $existingMember = $id !== '0' ? $this->teamMembersQuery()->where('u.id', $id)->first() : null;
        if ($id !== '0') {
            abort_unless($existingMember, 404);
        }
        if (! Schema::hasTable('team_memberships')) {
            return back()->withErrors(['project_ids' => 'Сначала выполните миграции базы данных.'])->withInput();
        }

        $firmaScope = HoldingScope::projectIdsFor($fid);
        $validationFirma = (string) ($existingMember->firma ?? $fid);

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
                    ->where('firma', $validationFirma)
                    ->ignore($id === '0' ? null : $id),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('users', 'phone')
                    ->where('firma', $validationFirma)
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
            'project_ids' => ['required', 'array', 'min:1'],
            'project_ids.*' => ['integer', Rule::in($firmaScope)],
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
            'city' => $stringValue($request->input('city')),
            'region' => $stringValue($request->input('region')),
            'description' => $stringValue($request->input('description')),
            'foto1' => $currentPhoto,
            'status' => $departmentValue,
            'firmuser' => '1',
            'firma' => $id === '0' ? $fid : $validationFirma,
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

        $memberId = DB::transaction(function () use ($id, $data, $validated, $firmaScope): int {
            $memberId = (int) User::edit($id, $data);
            $this->syncMemberships($memberId, $validated['project_ids'], $firmaScope);

            return $memberId;
        });

        if (Schema::hasColumn('users', 'status')) {
            DB::table('users')
                ->where('id', $memberId)
                ->update(['status' => $departmentValue]);
        }

        $stillInCurrentTeam = in_array((string) $fid, array_map('strval', $validated['project_ids']), true);
        $redirectRoute = $request->boolean('return_to_team') || ! $stillInCurrentTeam
            ? redirect()->route('team')
            : redirect()->route('team.show', ['id' => $memberId]);

        return $redirectRoute->with('success', 'Збережено');
    }

    public function destroy(Request $request)
    {
        $id = (string) $request->input('id', '0');
        $member = $this->teamMembersQuery()->where('u.id', $id)->first();
        abort_unless($member, 404);

        if (Schema::hasTable('team_memberships')) {
            DB::table('team_memberships')
                ->where('user_id', $member->id)
                ->where('project_id', $this->activeFid())
                ->delete();

            $hasOtherMemberships = DB::table('team_memberships')->where('user_id', $member->id)->exists();
            if (! $hasOtherMemberships && Schema::hasColumn('users', 'firmuser')) {
                DB::table('users')->where('id', $member->id)->update(['firmuser' => '0']);
            }
        }

        return redirect()->route('team')->with('success', 'Сотрудник удалён из команды компании. Пользователь сохранён.');
    }

    private function teamMembersQuery()
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('team_memberships')) {
            return DB::table('users')->whereRaw('1 = 0');
        }

        $fid = $this->activeFid();

        return DB::table('users as u')
            ->join('team_memberships as tm', 'tm.user_id', '=', 'u.id')
            ->where('tm.project_id', $fid)
            ->select('u.*')
            ->orderByDesc('u.top')
            ->orderBy('u.id');
    }

    private function activeFid(): string
    {
        $sessionFid = trim((string) session('fid', ''));
        if ($sessionFid !== '') {
            return $sessionFid;
        }

        return (string) (Auth::user()->firma ?? '');
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

    private function companyOptions(string $fid)
    {
        $projectIds = HoldingScope::projectIdsFor($fid);
        if (! Schema::hasTable('project') || $projectIds === []) {
            return collect();
        }

        return DB::table('project')
            ->whereIn('id', $projectIds)
            ->orderBy('num')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (object $project): object => (object) [
                'id' => (string) $project->id,
                'name' => trim((string) $project->name) ?: ('Project #' . $project->id),
            ]);
    }

    private function membershipProjectIds(int $userId, array $projectScope): array
    {
        if (! Schema::hasTable('team_memberships')) {
            return [];
        }

        return DB::table('team_memberships')
            ->where('user_id', $userId)
            ->whereIn('project_id', $projectScope)
            ->pluck('project_id')
            ->map(fn ($id): string => (string) $id)
            ->all();
    }

    private function addMemberships(int $userId, array $projectIds): void
    {
        $now = now();
        $rows = collect($projectIds)->map(fn ($projectId): array => [
            'user_id' => $userId,
            'project_id' => (int) $projectId,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::table('team_memberships')->insertOrIgnore($rows);
        if (Schema::hasColumn('users', 'firmuser')) {
            DB::table('users')->where('id', $userId)->update(['firmuser' => '1']);
        }
    }

    private function syncMemberships(int $userId, array $projectIds, array $projectScope): void
    {
        DB::transaction(function () use ($userId, $projectIds, $projectScope): void {
            DB::table('team_memberships')
                ->where('user_id', $userId)
                ->whereIn('project_id', $projectScope)
                ->delete();
            $this->addMemberships($userId, $projectIds);
        });
    }

    private function userDisplayName(object $user): string
    {
        $name = trim(implode(' ', array_filter([
            $user->secondname ?? '',
            $user->name ?? '',
            $user->fathername ?? '',
        ])));

        return $name !== '' ? $name : (trim((string) ($user->orgname ?? '')) ?: ('User #' . $user->id));
    }
}
