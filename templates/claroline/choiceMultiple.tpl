{if $courselevelexport}<?xml version="1.0" encoding="UTF-8"?>{/if}
<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1"
				xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
				xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_v2p1 http://www.imsglobal.org/xsd/imsqti_v2p1.xsd"
				identifier="{$assessmentitemidentifier}" title="{$assessmentitemtitle}" adaptive="false" timeDependent="false">
	<responseDeclaration identifier="{$questionid}" cardinality="{$responsedeclarationcardinality}" baseType="identifier">
		<correctResponse>
		{section name=answer loop=$correctresponses}
			<value>i{$correctresponses[answer].id}</value>
		{/section}
		</correctResponse>
		<mapping lowerBound="0" upperBound="1" defaultValue="0">
		{section name=answer loop=$answers}
			<mapEntry mapKey="i{$answers[answer].id}" mappedValue="0" />
		{/section}
		</mapping>
	</responseDeclaration>
	<outcomeDeclaration identifier="SCORE" cardinality="single" baseType="float">
		<defaultValue>
			<value>0</value>
		</defaultValue>
	</outcomeDeclaration>
	<outcomeDeclaration identifier="FEEDBACK" cardinality="{$responsedeclarationcardinality}" baseType="identifier"/>
	<itemBody>
			<choiceInteraction responseIdentifier="{$questionid}" shuffle="{$shuffle}" maxChoices="{$maxChoices}">
			<prompt>{$questionText}</prompt>
			{section name=answer loop=$answers}
				<simpleChoice identifier="i{$answers[answer].id}" fixed="false">{$answers[answer].answer}
				{if $answers[answer].feedback != ''}
					{if $answers[answer].answer != $correctresponse.answer}
					<feedbackInline identifier="i{$answers[answer].id}" outcomeIdentifier="FEEDBACK" showHide="show">{$answers[answer].feedback}</feedbackInline>
					{/if}
				{/if}
				</simpleChoice>
			{/section}
			</choiceInteraction>
	</itemBody>
	<responseProcessing>
		{section name=answer loop=$answers}
		<responseCondition>
			<responseIf>
				<{$operator}>
					<baseValue baseType="identifier">i{$answers[answer].id}</baseValue>
					<variable identifier="{$questionid}"/>
				</{$operator}>
				<setOutcomeValue identifier="SCORE">
					<sum>
						<variable identifier="SCORE"/>
						<baseValue baseType="float">{$answers[answer].fraction}</baseValue>
					</sum>
				</setOutcomeValue>
			</responseIf>
		</responseCondition>
		{/section}
		<responseCondition>
			<responseIf>
				<lte>
					<variable identifier="SCORE"/>
					<baseValue baseType="float">0</baseValue>
				</lte>
				<setOutcomeValue identifier="SCORE">
					<baseValue baseType="float">0</baseValue>
				</setOutcomeValue>
				<setOutcomeValue identifier="FEEDBACK2">
					<baseValue baseType="identifier">INCORRECT</baseValue>
				</setOutcomeValue>
			</responseIf>
			<responseElseIf>
				<gte>
					<variable identifier="SCORE"/>
					<baseValue baseType="float">0.99</baseValue>
				</gte>
				<setOutcomeValue identifier="SCORE">
					<baseValue baseType="float">1</baseValue>
				</setOutcomeValue>
				<setOutcomeValue identifier="FEEDBACK2">
					<baseValue baseType="identifier">CORRECT</baseValue>
				</setOutcomeValue>
			</responseElseIf>
			<responseElse>
				<setOutcomeValue identifier="FEEDBACK2">
					<baseValue baseType="identifier">PARTIAL</baseValue>
				</setOutcomeValue>
			</responseElse>
		</responseCondition>
        <setOutcomeValue identifier="FEEDBACK">
            <variable identifier="{$questionid}"/>
        </setOutcomeValue>		
	</responseProcessing>
	{if $correctfeedback != ''}
	<modalFeedback outcomeIdentifier="FEEDBACK2" identifier="CORRECT" showHide="show">{$correctfeedback}</modalFeedback>
	{/if}
	{if $partiallycorrectfeedback != ''}
	<modalFeedback outcomeIdentifier="FEEDBACK2" identifier="PARTIAL" showHide="show">{$partiallycorrectfeedback}</modalFeedback>
	{/if}
	{if $incorrectfeedback != ''}
	<modalFeedback outcomeIdentifier="FEEDBACK2" identifier="INCORRECT" showHide="show">{$incorrectfeedback}</modalFeedback>
	{/if}
	{if $generalfeedback != ''}
	<modalFeedback outcomeIdentifier="FEEDBACK" identifier="COMMENT" showHide="show">{$generalfeedback}</modalFeedback>
	{/if}
</assessmentItem>
