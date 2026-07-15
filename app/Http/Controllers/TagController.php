<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::withCount('citizens')->orderBy('name')->get();
        return view('tags.index', compact('tags'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:50|unique:eb_tags,name',
            'color'       => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'description' => 'nullable|string|max:255',
        ]);

        $tag = Tag::create($request->only('name', 'color', 'description'));

        ActivityLog::record('created', 'tag', $tag->id,
            "Tag \"{$tag->name}\" was created",
            ['name' => $tag->name, 'color' => $tag->color, 'description' => $tag->description]
        );

        return response()->json(['ok' => true, 'tag' => $tag]);
    }

    public function update(Request $request, Tag $tag)
    {
        $request->validate([
            'name'        => 'required|string|max:50|unique:eb_tags,name,' . $tag->id,
            'color'       => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'description' => 'nullable|string|max:255',
        ]);

        $old = $tag->only('name', 'color', 'description');
        $tag->update($request->only('name', 'color', 'description'));

        ActivityLog::record('updated', 'tag', $tag->id,
            "Tag \"{$tag->name}\" was updated",
            ['before' => $old, 'after' => $tag->only('name', 'color', 'description')]
        );

        return response()->json(['ok' => true, 'tag' => $tag]);
    }

    public function destroy(Request $request, Tag $tag)
    {
        $name        = $tag->name;
        $id          = $tag->id;
        $replaceId   = $request->integer('replace_with'); // 0 if none chosen
        $citizenIds  = $tag->citizens()->pluck('eb_citizen.id')->all();

        if ($replaceId && $replaceId !== $id) {
            $replacement = Tag::find($replaceId);
            if ($replacement) {
                // For each citizen that has this tag, attach the replacement (if not already there)
                foreach ($citizenIds as $cid) {
                    \DB::table('eb_citizen_tag')
                        ->updateOrInsert(
                            ['citizen_id' => $cid, 'tag_id' => $replaceId],
                        );
                }
            }
        }

        $tag->delete(); // pivot rows cascade-deleted automatically

        ActivityLog::record('deleted', 'tag', $id,
            "Tag \"{$name}\" was deleted" . ($replaceId ? " and replaced with tag #{$replaceId}" : ''),
            ['replaced_with' => $replaceId ?: null, 'affected_citizens' => count($citizenIds)]
        );

        return response()->json(['ok' => true]);
    }

    // Assign tags to a citizen from the show page modal
    public function assignToCitizen(Request $request, \App\Models\Citizen $citizen)
    {
        $request->validate([
            'tag_ids'   => 'nullable|array',
            'tag_ids.*' => 'integer|exists:eb_tags,id',
        ]);

        $before = $citizen->tags()->pluck('name')->sort()->values()->all();

        $citizen->tags()->sync($request->tag_ids ?? []);

        $after = $citizen->tags()->orderBy('name')->get();

        ActivityLog::record('tags_updated', 'citizen', $citizen->id,
            "Tags updated for {$citizen->lname}, {$citizen->fname}",
            ['before' => $before, 'after' => $after->pluck('name')->all()]
        );

        return response()->json([
            'ok'   => true,
            'tags' => $after,
        ]);
    }

    // All tags as JSON — used by citizen assign dropdown
    public function all()
    {
        return response()->json(Tag::orderBy('name')->get());
    }
}
