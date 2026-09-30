<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum Occupation: string
{
    use HasLabel;

    case Student = 'student';
    case EducationAcademia = 'education_academia';
    case Healthcare = 'healthcare';
    case CivilPublicService = 'civil_public_service';
    case BusinessOwnerEntrepreneur = 'business_owner_entrepreneur';
    case PrivateSectorEmployee = 'private_sector_employee';
    case IctTechnology = 'ict_technology';
    case EngineeringConstruction = 'engineering_construction';
    case FinanceBankingInsurance = 'finance_banking_insurance';
    case LegalServices = 'legal_services';
    case MediaCommunicationsCreative = 'media_communications_creative';
    case AgricultureFarming = 'agriculture_farming';
    case TransportLogistics = 'transport_logistics';
    case SkilledTradeArtisan = 'skilled_trade_artisan';
    case SalesMarketing = 'sales_marketing';
    case HospitalityTourism = 'hospitality_tourism';
    case SecurityServices = 'security_services';
    case ReligiousCommunityOrganisation = 'religious_community_organisation';
    case NgoDevelopmentSector = 'ngo_development_sector';
    case Retired = 'retired';
    case UnemployedSeekingWork = 'unemployed_seeking_work';
    case SelfEmployedFreelancer = 'self_employed_freelancer';
    case Homemaker = 'homemaker';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::EducationAcademia => 'Education / Academia',
            self::Healthcare => 'Healthcare',
            self::CivilPublicService => 'Civil / Public Service',
            self::BusinessOwnerEntrepreneur => 'Business Owner / Entrepreneur',
            self::PrivateSectorEmployee => 'Private-Sector Employee',
            self::IctTechnology => 'ICT / Technology',
            self::EngineeringConstruction => 'Engineering / Construction',
            self::FinanceBankingInsurance => 'Finance / Banking / Insurance',
            self::LegalServices => 'Legal Services',
            self::MediaCommunicationsCreative => 'Media / Communications / Creative Industry',
            self::AgricultureFarming => 'Agriculture / Farming',
            self::TransportLogistics => 'Transport / Logistics',
            self::SkilledTradeArtisan => 'Skilled Trade / Artisan',
            self::SalesMarketing => 'Sales / Marketing',
            self::HospitalityTourism => 'Hospitality / Tourism',
            self::SecurityServices => 'Security Services',
            self::ReligiousCommunityOrganisation => 'Religious / Community Organisation',
            self::NgoDevelopmentSector => 'NGO / Development Sector',
            self::Retired => 'Retired',
            self::UnemployedSeekingWork => 'Unemployed / Seeking Work',
            self::SelfEmployedFreelancer => 'Self-Employed / Freelancer',
            self::Homemaker => 'Homemaker',
            self::Other => 'Other',
        };
    }
}
