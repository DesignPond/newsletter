<?php

namespace designpond\newsletter\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use designpond\newsletter\Newsletter\Repo\NewsletterCampagneInterface;
use designpond\newsletter\Newsletter\Repo\NewsletterTypesInterface;
use designpond\newsletter\Newsletter\Repo\NewsletterContentInterface;
use designpond\newsletter\Newsletter\Worker\MailjetInterface;

use designpond\newsletter\Newsletter\Helper\Helper;

class CampagneController extends Controller
{
    protected $campagne;
    protected $type;
    protected $content;
    protected $mailjet;

    public function __construct(NewsletterCampagneInterface $campagne, NewsletterTypesInterface $type, NewsletterContentInterface $content, MailjetInterface $mailjet)
    {
        $this->campagne = $campagne;
        $this->type     = $type;
        $this->content  = $content;
        $this->mailjet  = $mailjet;

        setlocale(LC_ALL, 'fr_FR.UTF-8');

        view()->share('isNewsletter',true);
    }

    /**
     * Show the form for creation a campagne for newsletter.
     * GET /admin/campagne/create/{newsletter}
     *
     * @return \Illuminate\Http\Response
     */
    public function create($newsletter)
    {
        return view('newsletter::Backend.campagne.create')->with(['newsletter' => $newsletter]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $campagne = $this->campagne->find($id);
        $blocs    = $this->type->getAll();

        return view('newsletter::Backend.campagne.show')->with(['campagne' => $campagne, 'blocs' => $blocs]);
    }

    /**
     * Campagne
     * AJAX
     * @param  int  $id
     * @return Response
     */
    public function simple($id){

        return $this->campagne->find($id);
    }
    
    /**
     * Show the form for editing the campagne.
     * GET /admin/campagne/{id}/edit
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $campagne = $this->campagne->find($id);

        return view('newsletter::Backend.campagne.edit')->with(['campagne' => $campagne]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $campagne = $this->campagne->create(['sujet' => $request->input('sujet'), 'auteurs' => $request->input('auteurs'), 'newsletter_id' => $request->input('newsletter_id')]);

        $this->mailjet->setList($campagne->newsletter->list_id);

        $created = $this->mailjet->createCampagne($campagne);

        if(!$created)
        {
            throw new \designpond\newsletter\Exceptions\CampagneCreationException('Problème avec la création de campagne sur mailjet');
        }

        return redirect('build/campagne/'.$campagne->id)->with(['status' => 'success' , 'message' => 'Campagne crée']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $campagne = $this->campagne->update($request->all());

        return redirect('build/campagne/'.$campagne->id)->with(['status' => 'success' , 'message' => 'Campagne édité']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $campagne = $this->campagne->find($id);
        $campagne->content()->delete();

        $this->campagne->delete($id);

        return redirect()->back()->with(['status' => 'success', 'message' => 'Campagne supprimée']);
    }

}
