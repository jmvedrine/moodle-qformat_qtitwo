{if $courselevelexport}<?xml version="1.0" encoding="UTF-8"?>{/if}
<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_item_v2p1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_item_v2p1 http://www.imsglobal.org/xsd/imsqti_item_v2p1.xsd" identifier="{$assessmentitemidentifier}" title="{$assessmentitemtitle}" adaptive="false" timeDependent="false">
	<responseDeclaration identifier="{$questionid}" cardinality="{$responsedeclarationcardinality}" baseType="string">
		<correctResponse>
		{section name=answer loop=$correctresponses}
			<value>{$correctresponses[answer].answer}</value>
		{/section}
		</correctResponse>
		<mapping lowerBound="0" upperBound="1" defaultValue="0">
		{section name=answer loop=$answers}
			{if $answers[answer].fraction != 0}
			<mapEntry mapKey="{$answers[answer].answer}" mappedValue="{$answers[answer].fraction}" caseSensitive="{$usecase}" />
			{/if}
		{/section}
		</mapping>
	</responseDeclaration>
	<outcomeDeclaration identifier="SCORE" cardinality="single" baseType="float">
		<defaultValue>
			<value>{$defaultmark}</value>
		</defaultValue>
    </outcomeDeclaration>
	<itemBody>
    	<prompt>{$questionText}</prompt>
		<textEntryInteraction responseIdentifier="{$questionid}" expectedLength="15">
		</textEntryInteraction>
	</itemBody>
	<responseProcessing xmlns="http://www.imsglobal.org/xsd/imsqti_item_v2p1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_item_v2p1 ../imsqti_item_v2p1.xsd">
		<responseCondition>
			<responseIf>
				<isNull>
					<variable identifier="{$questionid}"/>
				</isNull>
				<setOutcomeValue identifier="SCORE">
					<baseValue baseType="integer">0</baseValue>
				</setOutcomeValue>
			</responseIf>
			<responseElse>
				<setOutcomeValue identifier="SCORE">
					<mapResponse identifier="{$questionid}"/>
				</setOutcomeValue>
			</responseElse>
		</responseCondition>
	</responseProcessing>
</assessmentItem>
