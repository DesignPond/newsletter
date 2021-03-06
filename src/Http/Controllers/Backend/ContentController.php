<?php

namespace designpond\newsletter\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use designpond\newsletter\Http\Requests\ContentRequest;

use designpond\newsletter\Newsletter\Repo\NewsletterContentInterface;
use designpond\newsletter\Newsletter\Repo\NewsletterClipboardInterface;
use designpond\newsletter\Newsletter\Repo\NewsletterCampagneInterface;
use designpond\newsletter\Newsletter\Helper\Helper;

class ContentController extends Controller
{
    protected $clipboard;
    protected $content;
    protected $campagne;

    public function __construct(NewsletterContentInterface $content, NewsletterClipboardInterface $clipboard, NewsletterCampagneInterface $campagne)
    {
        $this->content   = $content;
        $this->campagne  = $campagne;
        $this->clipboard = $clipboard;
    }

    public function show($id)
    {
        $contents   = $this->content->getByCampagne($id);
        $campagne   = $this->campagne->find($id);
        $clipboards = $this->clipboard->getAll();

        return view('newsletter::Backend.build.sorting')->with(['contents' => $contents, 'campagne' => $campagne, 'clipboards' => $clipboards]);
    }

    /**
     * Add bloc to newsletter
     * POST data
     * @return Response
     */
    public function store(ContentRequest $request){

        $data = $request->all();

        $upload = new Helper();

        // image resize
        if(isset($data['image']) && !empty($data['image']))
        {
            $upload->resizeImage($data['image'],$data['type_id']);
        }

        $this->content->create($data);

        alert()->success('Bloc ajouté');

        return redirect('build/campagne/'.$data['campagne'].'#componant');

    }

    /**
     * Edit bloc from newsletter
     * POST data
     * @return Response
     */
    public function update(Request $request){

        $contents = $this->content->update($request->all());

        alert()->success('Bloc édité');

        return redirect('build/campagne/'.$contents->newsletter_campagne_id.'#componant');
    }

    /**
     * Remove bloc from newsletter
     * POST remove
     * AJAX
     * @return Response
     */
    public function destroy(Request $request){

        $this->content->delete($request->input('id'));

        return 'ok';
    }

    /**
     * Sorting bloc newsletter
     * POST remove
     * AJAX
     * @return Response
     */
    public function sorting(Request $request){

        $data = $request->all();

        $contents = $this->content->updateSorting($data['bloc_rang']);

        return 'ok';
    }

    /**
     * Sorting bloc newsletter
     * POST remove
     * AJAX
     * @return Response
     */
    public function sortingGroup(Request $request){

        $model  = new \App\Droit\Arret\Entities\Groupe();
        $helper = new Helper();
        $data = $request->all();

        $groupe_rang = $data['groupe_rang'];
        $groupe_id   = $data['groupe_id'];

        $arrets = $helper->prepareCategories($groupe_rang);

        $groupe = $model->find($groupe_id);
        $groupe->arrets()->sync($arrets);

        print_r($groupe);

    }

}
