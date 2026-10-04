<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_profiles', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->json('content');
            $table->foreignUuid('updated_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('club_profiles')->insert([
            'id' => 1,
            'content' => json_encode([
                'cover' => [
                    'club_name' => 'Musuwa Nation Investment Club',
                    'tagline' => 'Building Wealth. Developing Men. Creating Legacy.',
                    'established' => '2026',
                    'motto' => 'Poverty is not a legacy',
                ],
                'identity' => [
                    'heading' => 'A community built for disciplined progress',
                    'body' => "Musuwa Nation Investment Club is a member-driven community of ambitious and disciplined men committed to building wealth, developing leadership and creating a lasting legacy.\nWe believe that people working together under clear rules can create opportunities that may be difficult to achieve individually. Our members contribute capital, exchange knowledge, evaluate opportunities and hold one another accountable.\nMNIC is more than a savings group. It is an institution in formation - designed to strengthen its members personally, professionally and financially while building assets and opportunities that can outlive its founders.",
                    'principles' => "Member-driven|Members shape the club's direction through participation, voting and shared responsibility.\nLong-term|We prioritise sustainable value and generational impact over quick, speculative gains.\nAccountable|Capital, decisions and responsibilities must be recorded, reported and reviewable.",
                ],
                'purpose' => [
                    'heading' => 'Ambition with direction',
                    'vision' => 'To become a trusted and influential investment community that develops principled leaders, builds sustainable wealth and creates opportunities for future generations.',
                    'mission' => 'To mobilise member capital, knowledge and networks; identify responsible investment opportunities; strengthen financial discipline; and promote the personal, professional and economic advancement of every member.',
                    'promise' => "Build with patience, discipline and a clear long-term objective.\nProtect trust through transparent decisions and responsible stewardship.\nDevelop members as investors, professionals, leaders and accountable men.\nCreate value that benefits present members and future generations.",
                ],
                'pillars' => [
                    'heading' => 'Five pillars of collective progress',
                    'items' => "Capital formation|Build investable capital through consistent contributions, clear targets and responsible treasury management.\nResponsible investment|Evaluate opportunities through research, risk assessment, collective approval and ongoing monitoring.\nMember development|Strengthen financial knowledge, professional competence, leadership and personal wellbeing.\nStrong governance|Protect funds and relationships through rules, reporting, accountability and conflict management.\nLegacy creation|Build assets, businesses, partnerships and institutions capable of generating lasting value beyond the founding membership.",
                ],
                'operating_model' => [
                    'heading' => 'From contribution to accountable investment',
                    'intro' => 'Our approach is designed to turn consistent member participation into protected capital, informed decisions and measurable progress.',
                    'steps' => "Contribute|Members meet agreed financial commitments and every contribution is recorded.\nProtect|Funds are held through approved channels with clear authorisation and reporting controls.\nResearch|Opportunities are screened for capital needs, risk, return, liquidity and supervision.\nDecide|Members receive a clear proposal and approve investments under agreed voting rules.\nMonitor|Performance, risks, expenses and key decisions are reported to the membership.\nGrow|Returns are retained, reinvested or distributed according to approved club rules.",
                ],
                'investment' => [
                    'heading' => 'Patient capital. Informed decisions. Sustainable value.',
                    'intro' => 'MNIC does not invest merely because money is available. Every opportunity must be understood, evaluated and approved within the club\'s governance framework.',
                    'look_for' => "Clear ownership, responsibilities and legal structure.\nUnderstandable risks and a realistic path to return.\nCredible management and limited dependence on one individual.\nAppropriate liquidity, time horizon and exit options.\nAlignment with the club's capital capacity and long-term objectives.",
                    'avoid' => "Guaranteed-return promises and pressure to invest quickly.\nUnresearched speculation or unfamiliar ventures.\nInvestments without documentation, reporting or an exit plan.\nCommitting the entire treasury without a liquidity reserve.\nUndisclosed conflicts of interest or informal control of club assets.",
                    'priority' => 'Build a consistent contribution record, protect liquidity, improve member investment knowledge and develop a researched pipeline before committing significant capital to direct investments.',
                ],
                'governance' => [
                    'heading' => 'Transparency is part of the investment strategy',
                    'intro' => 'Strong relationships are protected by clear authority, documented decisions and financial information that members can verify.',
                    'principles' => "Member authority|Major decisions remain subject to member approval under agreed quorum and voting rules.\nSeparation of duties|No one person should collect, authorise, reconcile and report the same transaction alone.\nFinancial reporting|Contributions, expenses, balances and investments should be reconciled and reported regularly.\nConflict disclosure|Members disclose personal interests and withdraw from decisions where their independence is affected.",
                    'accountability' => "Traceable contribution records and member statements.\nMultiple authorisations for movement of club funds.\nWritten investment proposals and recorded resolutions.\nIndependent reconciliation of records against account statements.\nRegular review of leadership, committees and investment performance.",
                ],
                'membership' => [
                    'heading' => 'Commitment today. Opportunity tomorrow.',
                    'requirements' => "Consistency in agreed contributions and responsibilities.\nActive participation in meetings, learning and collective decisions.\nRespect for the constitution, investment policy and club leadership.\nHonest communication, especially when commitments cannot be met.\nA willingness to contribute skills, networks and ideas - not money alone.",
                    'ambitions' => "Build a diversified portfolio of income-generating assets.\nEstablish or acquire sustainable businesses with clear governance.\nParticipate responsibly in property and professionally managed investments.\nDevelop partnerships with credible financial and business institutions.\nCreate opportunities that strengthen members and future generations.",
                    'enquiries' => 'Membership and partnership enquiries are handled through MNIC leadership.',
                    'closing' => 'Poverty is not a legacy',
                ],
            ], JSON_THROW_ON_ERROR),
            'updated_by_member_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('club_profiles');
    }
};
