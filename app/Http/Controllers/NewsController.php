<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

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

    public function apiShow(Request $request, int $id)
    {
        $fid = (string) $request->input('fid', session('fid', '2'));
        $locale = $this->resolveApiLocale($request);
        $item = News::findForView($id, $fid, $locale);

        if (!$item) {
            return response()->json(['message' => 'Новину не знайдено'], 404);
        }

        return response()->json(['item' => $item, 'locale' => $locale]);
    }

    public function apiStore(Request $request)
    {
        $user = $request->user();
        $roleStatus = max((int) ($user->ustype ?? 0), (int) ($user->idstatus ?? 0));

        if ($roleStatus < 3) {
            return response()->json(['message' => 'Недостатньо прав. Потрібен idstatus >= 3 або ustype >= 3'], 403);
        }

        $validated = $request->validate([
            'fid' => 'sometimes|integer|min:1',
            'title' => 'required|string|max:500',
            'kratko' => 'sometimes|string|max:1000',
            'txt' => 'sometimes|string',
            'publish' => 'sometimes|boolean',
            'article' => 'sometimes|boolean',
            'hot' => 'sometimes|boolean',
            'tags' => 'sometimes|string|max:500',
        ]);

        $fid = (string) ($validated['fid'] ?? 2);
        $isArticle = $request->boolean('article', true);
        $isPublish = $request->boolean('publish', true);

        $newsId = News::saveNews(0, $fid, [
            'title' => $validated['title'],
            'title_ua' => $validated['title'],
            'title_en' => $validated['title'],
            'kratko' => $validated['kratko'] ?? '',
            'kratko_ua' => $validated['kratko'] ?? '',
            'kratko_en' => $validated['kratko'] ?? '',
            'txt' => $validated['txt'] ?? '',
            'txt_ua' => $validated['txt'] ?? '',
            'txt_en' => $validated['txt'] ?? '',
            'foto' => '',
            'dt' => date('d-m-Y'),
            'time' => date('H:i:s'),
            'firma' => (int) $fid,
            'view' => $isPublish ? 1 : 0,
            'hot' => $request->boolean('hot', false) ? 1 : 0,
            'always' => 0,
            'article' => $isArticle ? 1 : 0,
            'tags' => $validated['tags'] ?? 'crypto,news',
            'htmlkeys' => '',
            'codesocnet' => '',
            'author' => (int) ($user->id ?? 0),
            'top' => '',
        ]);

        $locale = $this->resolveApiLocale($request);
        $item = News::findForView($newsId, $fid, $locale);

        return response()->json([
            'success' => true,
            'item' => $item,
        ], 201);
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

        return view('news.edit', compact('item'));
    }

    public function save(Request $request)
    {
        $fid = (string) session('fid', '');
        $id = (int) $request->input('id', 0);

        $titleRu = trim((string) $request->input('title', ''));
        $titleUa = trim((string) $request->input('title_ua', ''));
        $titleEn = trim((string) $request->input('title_en', ''));

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
            'kratko' => (string) $request->input('kratko', ''),
            'kratko_ua' => (string) $request->input('kratko_ua', ''),
            'kratko_en' => (string) $request->input('kratko_en', ''),
            'txt' => (string) $request->input('txt', ''),
            'txt_ua' => (string) $request->input('txt_ua', ''),
            'txt_en' => (string) $request->input('txt_en', ''),
            'foto' => $foto,
            'dt' => (string) $request->input('dt', date('d-m-Y')),
            'time' => $request->input('time') ?: date('H:i:s'),
            'firma' => (int) $fid,
            'view' => $request->boolean('view') ? 1 : 0,
            'hot' => $request->boolean('hot') ? 1 : 0,
            'always' => $request->boolean('always') ? 1 : 0,
            'article' => $request->boolean('article') ? 1 : 0,
            'tags' => (string) $request->input('tags', ''),
            'htmlkeys' => (string) $request->input('htmlkeys', ''),
            'codesocnet' => (string) $request->input('codesocnet', ''),
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
}
