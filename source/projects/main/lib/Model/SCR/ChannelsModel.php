<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Model\SCR;

use Slim\Container;
use Prototype\Model\SCR\Channel\ArticlesModel;
use Prototype\Model\SCR\Channel\EventsModel;
use Prototype\Model\SCR\Channel\PersonsModel;
use Prototype\Model\SCR\Article\SearchModel;

class ChannelsModel extends ScrModel
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()
            ->insert('id', null, true)
            ->insert('type', 'article')
            ->insert('label_id', null)
            ->insert('limit', 20)
            ->insert('skip', 0)
            ->insert('articleType')
            ->insert('eventType')
            ->insert('sortDirection')
            ->insert('sortProperty')
            ->insert('upcoming', 'true');
    }

    public function getPropertyArticles()
    {
        $state = $this->getState()->getValues();

        $articlesModel = new ArticlesModel($this->container);
        $articlesModel->setState($state);

        return $articlesModel->fetch();
    }

    public function getPropertyEvents()
    {
        $state = $this->getState()->getValues();

        $eventsModel = new EventsModel($this->container);
        $eventsModel->setState($state);

        return $eventsModel->fetch();
    }

    public function getPropertyPersons()
    {
        $state = $this->getState()->getValues();

        $personsModel = new PersonsModel($this->container);
        $personsModel->setState($state);

        return $personsModel->fetch();
    }

    public function fetch($raw = false)
    {
        $result = parent::fetch($raw);

        foreach ($result as $item) {
            if (isset($item['name'])) {
                $item['name'] = $this->translate($item['name']);
            }
        }

        if ($raw) {
            return $result;
        }

        return array_map(function ($channel) {
            $channel->getState()->insert('id', $channel['_id']);

            return $channel;
        }, $result);
    }

    public function getPropertyBlogArticles()
    {
        // {{ model.blogArticles }}
        return $this->getArticles([
            'articleType' => 'article.blog'
        ]);
    }

    public function getPropertyImpactArticles()
    {
        return $this->getArticles([
            'articleType' => 'article.impact'
        ]);
    }

    public function getPropertyCorporateNewsArticles()
    {
        return $this->getArticles([
            'articleType' => 'article.corporate_news'
        ]);
    }

    public function getPropertyBackgroundArticles()
    {
        return $this->getArticles([
            'articleType' => 'article.background'
        ]);
    }

    public function getPropertyOpinionArticles()
    {
        return $this->getArticles([
            'articleType' => 'article.opinion'
        ]);
    }

    public function getPropertyInterviewArticles()
    {
        return $this->getArticles([
            'articleType' => 'article.interview'
        ]);
    }

    public function getPropertySyndicatedArticles()
    {
        return $this->getArticles([
            'articleType' => 'article.syndicated'
        ]);
    }

    public function getPropertyreviewArticles()
    {
        return $this->getArticles([
            'articleType' => 'article.review'
        ]);
    }

    public function getPropertyTechnicalScientificArticles()
    {
        return $this->getArticles([
            'articleType' => 'article.technical_scientific'
        ]);
    }

    public function getPropertyDataReportArticles()
    {
        return $this->getArticles([
            'articleType' => 'article.data_report'
        ]);
    }

    public function getPropertyCommuniqueArticles()
    {
        return $this->getArticles([
            'articleType' => 'article.communique'
        ]);
    }

    public function getPropertyVacancyArticles()
    {
        return $this->getArticles([
            'articleType' => 'article.vacancy'
        ]);
    }

    public function getPropertyInternshipArticles()
    {
        return $this->getArticles([
            'articleType' => 'article.internship'
        ]);
    }

    public function getPropertyCallForExternalExpertArticles()
    {
        return $this->getArticles([
            'articleType' => 'article.call_for_external_expert'
        ]);
    }

    public function getPropertyCallForTenderArticles()
    {
        return $this->getArticles([
            'articleType' => 'article.call_for_tender'
        ]);
    }

    public function getPropertyCallForProposalArticles()
    {
        return $this->getArticles([
            'articleType' => 'article.call_for_proposal'
        ]);
    }

    public function getPropertyPartnershipArticles()
    {
        return $this->getArticles([
            'articleType' => 'article.partnership'
        ]);
    }

    public function getPropertyTenderAwardNoticeArticles()
    {
        return $this->getArticles([
            'articleType' => 'article.tender_award_notice'
        ]);
    }

    public function getPropertyTenderHighlightArticles()
    {
        return $this->getArticles([
            'articleType' => 'article.tender_highlight'
        ]);
    }

    public function getPropertySpeechArticles()
    {
        return $this->getArticles([
            'articleType' => 'article.speech'
        ]);
    }

    public function getPropertyConferenceEvents()
    {
        return $this->getEvents([
            'eventType' => 'Conference'
        ]);
    }

    public function getPropertyWorkshopEvents()
    {
        return $this->getEvents([
            'eventType' => 'Workshop'
        ]);
    }

    public function getPropertyTrainingOnlineEvents()
    {
        return $this->getEvents([
            'eventType' => 'Training (online)'
        ]);
    }

    public function getPropertyTrainingEvents()
    {
        return $this->getEvents([
            'eventType' => 'Training'
        ]);
    }

    public function getPropertyBriefingEvents()
    {
        return $this->getEvents([
            'eventType' => 'Briefing'
        ]);
    }

    public function getPropertyMeetingEvents()
    {
        return $this->getEvents([
            'eventType' => 'Meeting'
        ]);
    }

    public function getPropertyHackathonEvents()
    {
        return $this->getEvents([
            'eventType' => 'Hackathon'
        ]);
    }

    public function getPropertyForumEvents()
    {
        return $this->getEvents([
            'eventType' => 'Forum'
        ]);
    }

    public function getPropertyFieldVisitEvents()
    {
        return $this->getEvents([
            'eventType' => 'Field visit'
        ]);
    }

    public function getPropertyProjectEvents()
    {
        return $this->getEvents([
            'eventType' => 'Project'
        ]);
    }

    public function getPropertyOtherEvents()
    {
        return $this->getEvents([
            'eventType' => 'Other'
        ]);
    }

    private function getArticles($state = [])
    {
        $state = array_merge([
            'limit' => 5,
            'id' => $this['_id']
        ], $this->getState()->getValues(), $state);

        $model = new ArticlesModel($this->container);
        $model->setState($state);
        return $model->fetch();
    }

    private function getEvents($state = [])
    {
        $state = array_merge([
            'limit' => 5,
            'id' => $this['_id']
        ], $this->getState()->getValues(), $state);

        $model = new EventsModel($this->container);
        $model->setState($state);
        return $model->fetch();
    }

    public function getPropertyTotalEvents()
    {
        $model = new EventsModel($this->container);
        $model->setState($this->getState()->getValues());

        return $model->getTotal();
    }

    public function getPropertyTotalArticles()
    {
        $model = new ArticlesModel($this->container);
        $model->setState($this->getState()->getValues());

        return $model->getTotal();
    }
}
