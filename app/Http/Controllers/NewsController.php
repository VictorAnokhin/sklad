<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class NewsController extends Controller
{
    public function apiIndex(Request $request)
    {
        $fid = (string) $request->input('fid', session('fid', '2'));
        $limit = max(1, min(50, (int) $request->input('limit', 10)));
        $offset = max(0, (int) $request->input('offset', 0));
        $locale = $this->resolveApiLocale($request);
        $htmlkeys = trim((string) $request->input('htmlkeys', ''));
        $data = News::init($fid, $offset, $limit, $locale, $htmlkeys);

        return response()->json([
            'items' => $data['items'],
            'total' => $data['total'],
            'limit' => $limit,
            'offset' => $offset,
            'locale' => $locale,
            'htmlkeys' => $htmlkeys,
        ]);
    }

    public function apiShow(Request $request, string $identifier)
    {
        $fid = (string) $request->input('fid', session('fid', '2'));
        $locale = $this->resolveApiLocale($request);
        $item = News::findForView($identifier, $fid, $locale);

        if (!$item) {
            return response()->json(['message' => 'Новину не знайдено'], 404);
        }

        return response()->json(['item' => $item, 'locale' => $locale]);
    }

    public function apiStore(Request $request): JsonResponse
    {
        $user = $this->requireNewsEditor();
        $validated = $this->validateApiPayload($request);
        $fid = $this->resolveEditorFid($request, $user);
        $locale = $this->resolveApiLocale($request);
        $foto = (string) ($validated['foto'] ?? '');

        if ($request->hasFile('foto_upload')) {
            $uploadedFile = $request->file('foto_upload');
            if ($uploadedFile && $uploadedFile->isValid()) {
                $extension = $uploadedFile->getClientOriginalExtension() ?: $uploadedFile->extension() ?: 'jpg';
                $filename = 'news_' . date('Ymd_His') . '_' . uniqid() . '.' . strtolower($extension);
                $path = $uploadedFile->storeAs('files/news', $filename, 'public');
                $foto = $path ?: $foto;
            }
        }

        $newsId = News::saveNews(0, $fid, [
            'title' => $this->safeNewsLine($validated['title'] ?? '', 100),
            'title_ua' => $this->safeNewsLine($validated['title_ua'] ?? '', 100),
            'title_en' => $this->safeNewsLine($validated['title_en'] ?? '', 100),
            'url' => $this->safeNewsLine($validated['url'] ?? '', 100),
            'kratko' => $this->safeNewsText($validated['kratko'] ?? '', 65000),
            'kratko_ua' => $this->safeNewsText($validated['kratko_ua'] ?? '', 65000),
            'kratko_en' => $this->safeNewsText($validated['kratko_en'] ?? '', 65000),
            'txt' => $this->safeNewsText($validated['txt'] ?? '', 65000),
            'txt_ua' => $this->safeNewsText($validated['txt_ua'] ?? '', 65000),
            'txt_en' => $this->safeNewsText($validated['txt_en'] ?? '', 65000),
            'foto' => $foto,
            'dt' => (string) ($validated['dt'] ?? date('d-m-Y')),
            'time' => (string) ($validated['time'] ?? date('H:i:s')),
            'firma' => (int) $fid,
            'view' => (bool) ($validated['publish'] ?? $validated['view'] ?? true) ? 1 : 0,
            'hot' => (bool) ($validated['hot'] ?? false) ? 1 : 0,
            'always' => (bool) ($validated['always'] ?? false) ? 1 : 0,
            'article' => (bool) ($validated['article'] ?? false) ? 1 : 0,
            'tags' => $this->safeNewsKeywords($validated['tags'] ?? '', 30),
            'htmlkeys' => $this->safeNewsKeywords($validated['htmlkeys'] ?? '', 30),
            'codesocnet' => $this->safeNewsText($validated['codesocnet'] ?? '', 65000),
            'author' => (int) ($user->id ?? 0),
            'top' => (string) ($validated['top'] ?? ''),
        ]);

        return response()->json([
            'message' => 'Новину створено',
            'item' => News::findForView($newsId, $fid, $locale),
        ], 201);
    }

    public function apiStoreBySecret(Request $request): JsonResponse
    {
        $this->requireNewsPublishToken($request);
        $validated = $this->validateApiPayload($request);
        $fid = trim((string) ($validated['fid'] ?? ''));

        if ($fid === '') {
            throw ValidationException::withMessages([
                'fid' => 'Parameter "fid" is required.',
            ]);
        }

        $locale = $this->resolveApiLocale($request);
        $foto = (string) ($validated['foto'] ?? '');

        if ($request->hasFile('foto_upload')) {
            $uploadedFile = $request->file('foto_upload');
            if ($uploadedFile && $uploadedFile->isValid()) {
                $extension = $uploadedFile->getClientOriginalExtension() ?: $uploadedFile->extension() ?: 'jpg';
                $filename = 'news_' . date('Ymd_His') . '_' . uniqid() . '.' . strtolower($extension);
                $path = $uploadedFile->storeAs('files/news', $filename, 'public');
                $foto = $path ?: $foto;
            }
        }

        $newsId = News::saveNews(0, $fid, [
            'title' => $this->safeNewsLine($validated['title'] ?? '', 100),
            'title_ua' => $this->safeNewsLine($validated['title_ua'] ?? '', 100),
            'title_en' => $this->safeNewsLine($validated['title_en'] ?? '', 100),
            'url' => $this->safeNewsLine($validated['url'] ?? '', 100),
            'kratko' => $this->safeNewsText($validated['kratko'] ?? '', 65000),
            'kratko_ua' => $this->safeNewsText($validated['kratko_ua'] ?? '', 65000),
            'kratko_en' => $this->safeNewsText($validated['kratko_en'] ?? '', 65000),
            'txt' => $this->safeNewsText($validated['txt'] ?? '', 65000),
            'txt_ua' => $this->safeNewsText($validated['txt_ua'] ?? '', 65000),
            'txt_en' => $this->safeNewsText($validated['txt_en'] ?? '', 65000),
            'foto' => $foto,
            'dt' => (string) ($validated['dt'] ?? date('d-m-Y')),
            'time' => (string) ($validated['time'] ?? date('H:i:s')),
            'firma' => (int) $fid,
            'view' => (bool) ($validated['publish'] ?? $validated['view'] ?? true) ? 1 : 0,
            'hot' => (bool) ($validated['hot'] ?? false) ? 1 : 0,
            'always' => (bool) ($validated['always'] ?? false) ? 1 : 0,
            'article' => (bool) ($validated['article'] ?? false) ? 1 : 0,
            'tags' => $this->safeNewsKeywords($validated['tags'] ?? '', 30),
            'htmlkeys' => $this->safeNewsKeywords($validated['htmlkeys'] ?? '', 30),
            'codesocnet' => $this->safeNewsText($validated['codesocnet'] ?? '', 65000),
            'author' => 0,
            'top' => (string) ($validated['top'] ?? ''),
        ]);

        return response()->json([
            'message' => 'Новину створено',
            'item' => News::findForView($newsId, $fid, $locale),
        ], 201);
    }

    public function apiPublish(Request $request, int $id): JsonResponse
    {
        $user = $this->requireNewsEditor();
        $fid = $this->resolveEditorFid($request, $user);
        $locale = $this->resolveApiLocale($request);
        $item = News::findOwned($id, $fid);

        if (!$item) {
            return response()->json(['message' => 'Новину не знайдено'], 404);
        }

        News::saveNews($id, $fid, [
            'view' => 1,
            'dt' => (string) $request->input('dt', $item->dt ?: date('d-m-Y')),
            'time' => (string) $request->input('time', $item->time ?: date('H:i:s')),
        ]);

        return response()->json([
            'message' => 'Новину опубліковано',
            'item' => News::findForView($id, $fid, $locale),
        ]);
    }

    public function index(Request $request)
    {
        $fid = (string) session('fid', '');
        $pos = max(0, (int) $request->input('pos', 0));
        $perPage = 20;
        $locale = $this->resolveBackendLocale($request);

        $data = News::init($fid, $pos, $perPage, $locale);

        return view('news.index', [
            'items' => $data['items'],
            'total' => $data['total'],
            'pos' => $pos,
            'perPage' => $perPage,
        ]);
    }

    public function show(Request $request)
    {
        $fid = (string) session('fid', '');
        $id = (int) $request->input('id', 0);
        $locale = $this->resolveBackendLocale($request);
        $item = News::findForView($id, $fid, $locale);

        if (!$item) {
            return redirect()->route('news.index')->with('error', 'Новину не знайдено');
        }

        return view('news.show', compact('item'));
    }

    public function edit(Request $request)
    {
        $fid = (string) session('fid', '');
        $id = (int) $request->input('id', 0);

        if ($id > 0) {
            $item = News::findOwned($id, $fid);

            if (!$item) {
                return redirect()->route('news.index')->with('error', 'Новину не знайдено');
            }
        } else {
            $item = News::emptyNews($fid);
        }

        $item->photo_view = News::resolvePhoto((string) ($item->foto ?? ''));

        return view('news.edit', compact('item'));
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:100'],
            'title_ua' => ['nullable', 'string', 'max:100'],
            'title_en' => ['nullable', 'string', 'max:100'],
            'url' => ['nullable', 'string', 'max:100'],
            'kratko' => ['nullable', 'string', 'max:65000'],
            'kratko_ua' => ['nullable', 'string', 'max:65000'],
            'kratko_en' => ['nullable', 'string', 'max:65000'],
            'txt' => ['nullable', 'string', 'max:65000'],
            'txt_ua' => ['nullable', 'string', 'max:65000'],
            'txt_en' => ['nullable', 'string', 'max:65000'],
            'tags' => ['nullable', 'string', 'max:30'],
            'htmlkeys' => ['nullable', 'string', 'max:30'],
            'foto_upload' => ['nullable', 'image', 'max:5120'],
        ]);

        $fid = (string) session('fid', '');
        $id = (int) $request->input('id', 0);

        $titleRu = $this->safeNewsLine($validated['title'] ?? '', 100);
        $titleUa = $this->safeNewsLine($validated['title_ua'] ?? '', 100);
        $titleEn = $this->safeNewsLine($validated['title_en'] ?? '', 100);

        if ($titleRu === '' && $titleUa === '' && $titleEn === '') {
            return redirect()->back()->withInput()->with('error', 'Заповніть хоча б одну назву новини');
        }

        $foto = (string) $request->input('foto', '');
        if ($request->hasFile('foto_upload')) {
            $uploadedFile = $request->file('foto_upload');
            if ($uploadedFile && $uploadedFile->isValid()) {
                $extension = $uploadedFile->getClientOriginalExtension() ?: $uploadedFile->extension() ?: 'jpg';
                $filename = 'news_' . date('Ymd_His') . '_' . uniqid() . '.' . strtolower($extension);
                $path = $uploadedFile->storeAs('files/news', $filename, 'public');
                $foto = $path ?: $foto;
            }
        }

        $newsId = News::saveNews($id, $fid, [
            'title' => $titleRu,
            'title_ua' => $titleUa,
            'title_en' => $titleEn,
            'url' => $this->safeNewsLine($validated['url'] ?? '', 100),
            'kratko' => $this->safeNewsText($validated['kratko'] ?? '', 65000),
            'kratko_ua' => $this->safeNewsText($validated['kratko_ua'] ?? '', 65000),
            'kratko_en' => $this->safeNewsText($validated['kratko_en'] ?? '', 65000),
            'txt' => $this->safeNewsText($validated['txt'] ?? '', 65000),
            'txt_ua' => $this->safeNewsText($validated['txt_ua'] ?? '', 65000),
            'txt_en' => $this->safeNewsText($validated['txt_en'] ?? '', 65000),
            'foto' => $foto,
            'dt' => (string) $request->input('dt', date('d-m-Y')),
            'time' => $request->input('time') ?: date('H:i:s'),
            'firma' => (int) $fid,
            'view' => $request->boolean('view') ? 1 : 0,
            'hot' => $request->boolean('hot') ? 1 : 0,
            'always' => $request->boolean('always') ? 1 : 0,
            'article' => $request->boolean('article') ? 1 : 0,
            'tags' => $this->safeNewsKeywords($validated['tags'] ?? '', 30),
            'htmlkeys' => $this->safeNewsKeywords($validated['htmlkeys'] ?? '', 30),
            'author' => (int) session('user_id', 0),
            'top' => (string) $request->input('top', ''),
        ]);

        return redirect()->route('news.edit', ['id' => $newsId])->with('success', 'Новину збережено');
    }

    public function destroy(Request $request)
    {
        $fid = (string) session('fid', '');
        $id = (int) $request->input('id', 0);

        if ($id <= 0) {
            return redirect()->route('news.index')->with('error', 'Помилка видалення новини');
        }

        News::deleteNews($id, $fid);

        return redirect()->route('news.index')->with('success', 'Новину видалено');
    }

    private function validateApiPayload(Request $request): array
    {
        return $request->validate([
            'fid' => ['nullable', 'integer', 'min:1'],
            'title' => ['nullable', 'string', 'max:100', 'required_without_all:title_ua,title_en'],
            'title_ua' => ['nullable', 'string', 'max:100'],
            'title_en' => ['nullable', 'string', 'max:100'],
            'url' => ['nullable', 'string', 'max:100'],
            'kratko' => ['nullable', 'string', 'max:65000'],
            'kratko_ua' => ['nullable', 'string', 'max:65000'],
            'kratko_en' => ['nullable', 'string', 'max:65000'],
            'txt' => ['nullable', 'string', 'max:65000'],
            'txt_ua' => ['nullable', 'string', 'max:65000'],
            'txt_en' => ['nullable', 'string', 'max:65000'],
            'foto' => ['nullable', 'string', 'max:250'],
            'foto_upload' => ['nullable', 'image', 'max:5120'],
            'dt' => ['nullable', 'string', 'max:12'],
            'time' => ['nullable', 'date_format:H:i:s'],
            'publish' => ['nullable', 'boolean'],
            'view' => ['nullable', 'boolean'],
            'hot' => ['nullable', 'boolean'],
            'always' => ['nullable', 'boolean'],
            'article' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'string', 'max:30'],
            'htmlkeys' => ['nullable', 'string', 'max:30'],
            'codesocnet' => ['nullable', 'string', 'max:65000'],
            'top' => ['nullable', 'string', 'max:5'],
        ]);
    }

    private function safeNewsLine(mixed $value, int $maxLength): string
    {
        $text = preg_replace('/[\x00-\x1F\x7F]/u', '', (string) ($value ?? ''));
        $text = preg_replace('/\s+/u', ' ', trim(strip_tags((string) $text)));

        return mb_substr((string) $text, 0, $maxLength);
    }

    private function safeNewsKeywords(mixed $value, int $maxLength): string
    {
        $text = preg_replace('/[\x00-\x1F\x7F<>{}\[\]\\\\=;:*|~^$#@!?%&+]/u', '', (string) ($value ?? ''));
        $text = preg_replace('/[^\p{L}\p{M}\p{N}\s,._-]/u', '', (string) $text);
        $text = preg_replace('/\s+/u', ' ', trim(strip_tags((string) $text)));

        return mb_substr((string) $text, 0, $maxLength);
    }

    private function safeNewsText(mixed $value, int $maxLength): string
    {
        $text = (string) ($value ?? '');
        $text = preg_replace('/<\s*(script|style|iframe|object|embed)\b[^>]*>.*?<\s*\/\s*\1\s*>/isu', '', $text);
        $text = preg_replace('/<\s*(script|style|iframe|object|embed)\b[^>]*\/?>/isu', '', (string) $text);
        $text = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', (string) $text);
        $text = preg_replace('/\s+(href|src)\s*=\s*("[\s]*javascript:[^"]*"|\'[\s]*javascript:[^\']*\'|javascript:[^\s>]+)/iu', '', (string) $text);
        // All HTML tags preserved — only dangerous elements (script/style/iframe/object/embed) are stripped

        return mb_substr(trim((string) $text), 0, $maxLength);
    }

    private function requireNewsEditor(): object
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'Потрібна авторизація.');
        }

        $status = (int) (($user->idstatus ?? 0) ?: ($user->ustype ?? 0));
        if ($status < 3) {
            abort(403, 'Недостатньо прав для публікації новин.');
        }

        return $user;
    }

    private function requireNewsPublishToken(Request $request): void
    {
        $expectedToken = trim((string) config('services.news_publish.token', ''));
        if ($expectedToken === '') {
            abort(503, 'News publish token is not configured.');
        }

        $providedToken = trim((string) $request->header('X-News-Publish-Token', ''));
        if ($providedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            abort(403, 'Invalid news publish token.');
        }
    }

    private function resolveEditorFid(Request $request, object $user): string
    {
        $fid = trim((string) (($user->firma ?? '') ?: ($user->fid ?? '')));

        if ($fid === '') {
            $fid = trim((string) $request->input('fid', ''));
        }

        if ($fid === '') {
            throw ValidationException::withMessages([
                'fid' => 'Не вдалося визначити проект для новини.',
            ]);
        }

        return $fid;
    }
}
