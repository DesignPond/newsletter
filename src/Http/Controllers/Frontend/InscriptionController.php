<?php

namespace Designpond\Newsletter\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use designpond\newsletter\Http\Requests\SubscribeRequest;

use designpond\newsletter\Newsletter\Repo\NewsletterUserInterface;
use designpond\newsletter\Newsletter\Worker\MailjetServiceInterface;
use designpond\newsletter\Newsletter\Repo\NewsletterInterface;

class InscriptionController extends Controller
{
    protected $subscription;
    protected $worker;
    protected $newsletter;

    public function __construct(MailjetServiceInterface $worker, NewsletterUserInterface $subscription, NewsletterInterface $newsletter)
    {
        $this->worker        = $worker;
        $this->subscription  = $subscription;
        $this->newsletter    = $newsletter;
    }

    /**
     * Activate newsletter abo
     * GET //activation
     *
     * @return Response
     */
    public function activation($token,$newsletter_id)
    {
        // Activate the email on the website
        $user = $this->subscription->activate($token);

        if(!$user)
        {
            alert()->danger('Le jeton ne correspond pas ou à expiré');

            return redirect('/');
        }

        $newsletter = $this->newsletter->find($newsletter_id);

        if(!$newsletter)
        {
            alert()->danger('Cette newsletter n\'existe pas');

            return redirect('/');
        }

        //Subscribe to mailjet
        $this->worker->setList($newsletter->list_id);
        $result = $this->worker->subscribeEmailToList($user->email);

        if(!$result){

            alert()->danger('Problème');

            return redirect('/');
        }

        alert()->success('Vous êtes maintenant abonné à la newsletter');

        return redirect('/');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function subscribe(SubscribeRequest $request)
    {
        $subscribe = $this->subscription->findByEmail($request->input('email'));

        if($subscribe)
        {
            if(!$subscribe->activated_at)
            {
                alert()->warning('Cet email existe déjà');

                return redirect('/')->withInput()->with('resend', true);
            }

            $subscriptions = $subscribe->subscriptions->pluck('id')->all();

            if(in_array($request->input('newsletter_id'),$subscriptions))
            {
                alert()->warning('Vous êtes déjà inscrit à la newsletter');

                return redirect($request->input('return_path', '/'));
            }
        }
        else
        {
            // Subscribe user with activation token to website list and sync newsletter abos
            $subscribe = $this->subscription->create(['email' => $request->input('email'), 'activation_token' => md5($request->email.\Carbon\Carbon::now()) ]);
        }

        $subscribe->subscriptions()->attach($request->input('newsletter_id'));

        \Mail::send('newsletter::Email.confirmation', array('token' => $subscribe->activation_token, 'newsletter_id' => $request->input('newsletter_id')), function($message) use ($subscribe)
        {
            $message->to($subscribe->email, $subscribe->email)->subject('Inscription!');
        });

        alert()->success('<strong>Merci pour votre inscription!</strong><br/>Veuillez confirmer votre adresse email en cliquant le lien qui vous a été envoyé par email');

        return redirect($request->input('return_path', '/'));
    }

    /**
     * Remove the specified resource from storage.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function unsubscribe(SubscribeRequest $request)
    {
        // find the abo
        $abonne = $this->subscription->findByEmail( $request->input('email') );

        if(!$abonne){
            return redirect('/')->with(['status'  => 'danger', 'message' => '<strong>Aucun abonné avec cet email</strong>']);
        }

        // Sync the abos to newsletter we have
        $abonne->subscriptions()->detach($request->input('newsletter_id'));

        $newsletter = $this->newsletter->find($request->input('newsletter_id'));

        if(!$newsletter)
        {
            alert()->danger('Cette newsletter n\'existe pas');

            return redirect('/');
        }

        //Subscribe to mailjet
        $this->worker->setList($newsletter->list_id);
        
        if(!$this->worker->removeContact($abonne->email))
        {
            throw new \designpond\newsletter\Exceptions\SubscribeUserException('Erreur synchronisation email vers mailjet');
        }

        // Delete person only if no subscription left
        if($abonne->subscriptions->isEmpty())
        {
            $this->subscription->delete($abonne->email);
        }

        $back = $request->input('return_path', '/');

        alert()->success('<strong>Vous avez été désinscrit</strong>');

        return redirect($back);
    }

    /**
     * Resend activation link email
     * POST /inscription/resend/email
     *
     * @return Response
     */
    public function resend(Request $request)
    {
        $subscribe = $this->subscription->findByEmail($request->input('email'));

        \Mail::send('newsletter::Email.confirmation', ['token' => $subscribe->activation_token, 'newsletter_id' => $request->input('newsletter_id')], function($message) use ($subscribe)
        {
            $message->to($subscribe->email, $subscribe->email)->subject('Inscription!');
        });

        alert()->success('<strong>Lien d\'activation envoyé</strong>');

        return redirect('/');
    }
}
