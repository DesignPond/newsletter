<?php

namespace designpond\newsletter\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use designpond\newsletter\Newsletter\Repo\NewsletterInterface;
use designpond\newsletter\Newsletter\Repo\NewsletterUserInterface;
use designpond\newsletter\Newsletter\Worker\MailjetServiceInterface;
use designpond\newsletter\Newsletter\Worker\SubscriptionWorkerInterface;

use designpond\newsletter\Http\Requests\RemoveNewsletterUserRequest;

class SubscriberController extends Controller
{
    protected $subscriber;
    protected $newsletter;
    protected $worker;
    protected $subscription_worker;

    public function __construct(NewsletterUserInterface $subscriber, NewsletterInterface $newsletter, MailjetServiceInterface $worker,SubscriptionWorkerInterface $subscription_worker)
    {
        $this->subscriber = $subscriber;
        $this->newsletter = $newsletter;
        $this->worker     = $worker;
        $this->subscription_worker = $subscription_worker;

        view()->share('isNewsletter',true);
    }

    /**
     * Display a listing of the resource.
     * GET /subscriber
     *
     * @return Response
     */
    public function index()
    {
        return view('newsletter::Backend.subscribers.index');
    }

    /**
     * Display a listing of tsubscribers for ajax
     * GET /subscriber/getAllAbos
     *
     * @return Response
     */
    public function subscribers(Request $request)
    {
        $order  = $request->input('order');
        $search = $request->input('search',null);
        $search = ($search ? $search['value'] : null);

        return $this->subscriber->get_ajax(
            $request->input('draw'), $request->input('start'), $request->input('length'), $order[0]['column'], $order[0]['dir'], $search
        );
    }

    /**
     * Show the form for creating a new resource.
     * GET /subscriber/create
     *
     * @return Response
     */
    public function create()
    {
        $newsletter = $this->newsletter->getAll();

        return view('newsletter::Backend.subscribers.create')->with(['newsletter' => $newsletter ]);
    }

    /**
     * Store a newly created resource in storage.
     * POST /subscriber
     *
     * @return Response
     */
    public function store(Request $request)
    {
        // Subscribe user with activation token to website list and sync newsletter abos
        $subscribe = $this->subscriber->create(
            [
                'email'         => $request->input('email'),
                'activated_at'  => \Carbon\Carbon::now(),
                'newsletter_id' => $request->input('newsletter_id')
            ]
        );

        //Subscribe to mailjet
        $lists = $request->input('newsletter_id');

        if(!empty($lists))
        {
            foreach($lists as $list)
            {
                $newsletter = $this->newsletter->find($list);
                $this->worker->setList($newsletter->list_id);
                $this->worker->subscribeEmailToList($subscribe->email);
            }
        }

        alert()->success('Abonné ajouté');

        return redirect('build/subscriber');
    }

    /**
     * Show the form for editing the specified resource.
     * GET /subscriber/{id}/edit
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $subscriber = $this->subscriber->find($id);
        $newsletter = $this->newsletter->getAll();

        return view('newsletter::Backend.subscribers.show')->with(['subscriber' => $subscriber , 'newsletter' => $newsletter]);
    }

    /**
     * Update the specified resource in storage.
     * PUT /subscriber/{id}
     *
     * @param  int  $id
     * @return Response
     */
    public function update(RemoveNewsletterUserRequest $request, $id)
    {
        $activated_at = ($request->input('activation') ? date('Y-m-d G:i:s') : null);
        $subscriber   = $this->subscriber->find($id);

        $new = $request->input('newsletter_id',[]);
        $has = $subscriber->subscriptions->pluck('id')->all();

        $added   = array_diff($new, $has);
        $removed = array_diff(array_unique(array_merge($new, $has)), $new);

        // if email edited we have to change it
        if($request->input('email') != $subscriber->email){
            // unsubscribe all and delete
            $this->subscription_worker->unsubscribe($subscriber,$has);

            // make new subscriber and add all
            $newsubscriber = $this->subscriber->create([
                'email' => $request->input('email'), 'activated_at' => $activated_at, 'activation_token' => md5($request->input('email').\Carbon\Carbon::now()),
            ]);

            $this->subscription_worker->subscribe($newsubscriber,$new);

            alert()->success('Abonné édité');
            return redirect('build/subscriber/'.$newsubscriber->id);
        }
        else{
            $subscriber = $this->subscriber->update(['id' => $id,'activated_at' => $request->input('activation') ? date('Y-m-d G:i:s') : null]);

            $this->subscription_worker->subscribe($subscriber,$added);
            $subscriber = $this->subscription_worker->unsubscribe($subscriber,$removed);

            if(!$subscriber){
                alert()->success('Abonné édité et supprimé');
                return redirect('build/subscriber');
            }

            alert()->success('Abonné édité');
            return redirect('build/subscriber/'.$subscriber->id);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /subscriber/{id}
     *
     * @param  int  $email
     * @return Response
     */
    public function destroy($id,Request $request)
    {
        // Validate the email
        $this->validate($request, array('email' => 'required'));

        // find the abo
        $subscriber = $this->subscriber->findByEmail($request->email);

        // Remove all the abos we have
        $subscriber->subscriptions()->detach();

        // Delete the subscriber from DB
        $this->subscriber->delete($subscriber->email);

        $newsletters = $this->newsletter->getAll();

        foreach($newsletters as $newsletter)
        {
            $this->worker->setList($newsletter->list_id);

            // Remove subscriber from list mailjet
            if(!$this->worker->removeContact($subscriber->email))
            {
                throw new \designpond\newsletter\Exceptions\DeleteUserException('Erreur avec la suppression de l\'abonnés sur mailjet');
            }
        }

        alert()->success('Abonné supprimé');

        return redirect('build/subscriber');
    }
}
