{if $courselevelexport}<?xml version="1.0" encoding="UTF-8"?>{/if}
<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_item_v2p1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_item_v2p1 ./imsqti_item_v2p1.xsd" identifier="{$assessmentitemidentifier}" title="{$assessmentitemtitle}" adaptive="false" timeDependent="false">
	<responseDeclaration identifier="{$questionid}" cardinality="single" baseType="string"/>
	<outcomeDeclaration identifier="SCORE" cardinality="single" baseType="float">
		<defaultValue>
			<value>{$defaultmark}</value>
		</defaultValue>
	</outcomeDeclaration>
	<itemBody>
		<extendedTextInteraction responseIdentifier="{$questionid}" expectedLength="600">
			<prompt>{$questionText}</prompt>
		</extendedTextInteraction>
	</itemBody>
{if $question->feedback != ''}
	<modalFeedback outcomeIdentifier="FEEDBACK" identifier="{$questionid}" showHide="hide">{$question->feedback}</modalFeedback>
	<modalFeedback outcomeIdentifier="FEEDBACK" identifier="{$questionid}" showHide="show">{$question->feedback}</modalFeedback>
{/if}
</assessmentItem>
