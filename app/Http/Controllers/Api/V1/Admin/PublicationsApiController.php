<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\StorePublicationRequest;
use App\Http\Requests\UpdatePublicationRequest;
use App\Http\Resources\Admin\PublicationResource;
use App\Models\Publication;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicationsApiController extends Controller
{
    use MediaUploadingTrait;

    public function index()
    {
        abort_if(Gate::denies('publication_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new PublicationResource(Publication::with(['categories'])->get());
    }

    public function store(StorePublicationRequest $request)
    {
        $publication = Publication::create($request->all());
        $publication->categories()->sync($request->input('categories', []));
        foreach ($request->input('photos', []) as $file) {
            $publication->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('photos');
        }

        return (new PublicationResource($publication))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Publication $publication)
    {
        abort_if(Gate::denies('publication_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new PublicationResource($publication->load(['categories']));
    }

    public function update(UpdatePublicationRequest $request, Publication $publication)
    {
        $publication->update($request->all());
        $publication->categories()->sync($request->input('categories', []));
        if (count($publication->photos) > 0) {
            foreach ($publication->photos as $media) {
                if (! in_array($media->file_name, $request->input('photos', []))) {
                    $media->delete();
                }
            }
        }
        $media = $publication->photos->pluck('file_name')->toArray();
        foreach ($request->input('photos', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $publication->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('photos');
            }
        }

        return (new PublicationResource($publication))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Publication $publication)
    {
        abort_if(Gate::denies('publication_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $publication->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
