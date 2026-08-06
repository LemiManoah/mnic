<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The club's elected offices.
 *
 * Deliberately separate from ClubRole: a position is a title the membership
 * elected somebody to, while a role is what the application lets them do. A
 * Chief Whip has a position but needs no special permission; an Administrator
 * has a permission but holds no elected office.
 */
enum ClubPosition: string
{
    case Chairperson = 'chairperson';
    case ViceChairperson = 'vice-chairperson';
    case GeneralSecretary = 'general-secretary';
    case AssistantGeneralSecretary = 'assistant-general-secretary';
    case Treasurer = 'treasurer';
    case AssistantTreasurer = 'assistant-treasurer';
    case Mobilizer = 'mobilizer';
    case AssistantMobilizer = 'assistant-mobilizer';
    case ChiefWhip = 'chief-whip';
    case AssistantChiefWhip = 'assistant-chief-whip';

    public function label(): string
    {
        return match ($this) {
            self::Chairperson => 'Chairperson',
            self::ViceChairperson => 'Vice Chairperson',
            self::GeneralSecretary => 'General Secretary',
            self::AssistantGeneralSecretary => 'Assistant General Secretary',
            self::Treasurer => 'Treasurer',
            self::AssistantTreasurer => 'Assistant Treasurer',
            self::Mobilizer => 'Mobilizer',
            self::AssistantMobilizer => 'Assistant Mobilizer',
            self::ChiefWhip => 'Chief Whip',
            self::AssistantChiefWhip => 'Assistant Chief Whip',
        };
    }
}
