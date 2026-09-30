<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum VolunteerCategory: string
{
    use HasLabel;

    case GrassrootsMobilisation = 'grassroots_mobilisation';
    case CommunityEngagement = 'community_engagement';
    case DoorToDoorCanvassing = 'door_to_door_canvassing';
    case DigitalSocialMedia = 'digital_social_media';
    case ContentCreationCreativeDesign = 'content_creation_creative_design';
    case MediaPublicityCommunications = 'media_publicity_communications';
    case PhotographyVideoProduction = 'photography_video_production';
    case EventsRallySupport = 'events_rally_support';
    case VolunteerCoordination = 'volunteer_coordination';
    case PhoneCallCentreSupport = 'phone_call_centre_support';
    case ResearchPolicySupport = 'research_policy_support';
    case DataIctDigitalOperations = 'data_ict_digital_operations';
    case LogisticsFieldSupport = 'logistics_field_support';
    case TranslationLocalLanguages = 'translation_local_languages';
    case AdministrationOfficeSupport = 'administration_office_support';
    case FundraisingResourceMobilisation = 'fundraising_resource_mobilisation';
    case TrainingVolunteerDevelopment = 'training_volunteer_development';
    case LegalComplianceSupport = 'legal_compliance_support';
    case ElectionDayOperations = 'election_day_operations';
    case PollingPartyAgent = 'polling_party_agent';
    case OtherGeneralVolunteer = 'other_general_volunteer';

    public function label(): string
    {
        return match ($this) {
            self::GrassrootsMobilisation => 'Grassroots Mobilisation',
            self::CommunityEngagement => 'Community Engagement',
            self::DoorToDoorCanvassing => 'Door-to-Door Canvassing',
            self::DigitalSocialMedia => 'Digital & Social Media',
            self::ContentCreationCreativeDesign => 'Content Creation & Creative Design',
            self::MediaPublicityCommunications => 'Media, Publicity & Communications',
            self::PhotographyVideoProduction => 'Photography & Video Production',
            self::EventsRallySupport => 'Events & Rally Support',
            self::VolunteerCoordination => 'Volunteer Coordination',
            self::PhoneCallCentreSupport => 'Phone / Call Centre Support',
            self::ResearchPolicySupport => 'Research & Policy Support',
            self::DataIctDigitalOperations => 'Data, ICT & Digital Operations',
            self::LogisticsFieldSupport => 'Logistics & Field Support',
            self::TranslationLocalLanguages => 'Translation & Local Languages',
            self::AdministrationOfficeSupport => 'Administration & Office Support',
            self::FundraisingResourceMobilisation => 'Fundraising & Resource Mobilisation',
            self::TrainingVolunteerDevelopment => 'Training & Volunteer Development',
            self::LegalComplianceSupport => 'Legal & Compliance Support',
            self::ElectionDayOperations => 'Election-Day Operations',
            self::PollingPartyAgent => 'Polling/Party Agent',
            self::OtherGeneralVolunteer => 'Other / General Volunteer',
        };
    }
}
