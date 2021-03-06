<?php
if ( $_REQUEST['values'] && $_POST['values'] && AllowEdit())
{
	foreach ( (array) $_REQUEST['values'] as $id => $columns)
	{
		//FJ fix SQL bug invalid sort order
		if (empty($columns['SORT_ORDER']) || is_numeric($columns['SORT_ORDER']))
		{
			if ( $id!='new')
			{
				$sql = "UPDATE STUDENT_ENROLLMENT_CODES SET ";

				foreach ( (array) $columns as $column => $value)
				{
					$sql .= $column."='".$value."',";
				}
				$sql = mb_substr($sql,0,-1) . " WHERE ID='".$id."'";
				DBQuery($sql);
			}
			else
			{
				$sql = "INSERT INTO STUDENT_ENROLLMENT_CODES ";

				$fields = 'ID,SYEAR,';
				$values = db_seq_nextval('STUDENT_ENROLLMENT_CODES_SEQ').",'".UserSyear()."',";

				$go = 0;
				foreach ( (array) $columns as $column => $value)
				{
					if ( !empty($value) || $value=='0')
					{
						$fields .= $column.',';
						$values .= "'".$value."',";
						$go = true;
					}
				}
				$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';

				if ( $go)
					DBQuery($sql);
			}
		}
		else
			$error[] = _('Please enter a valid Sort Order.');
	}
}

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'remove' && AllowEdit() )
{
	if ( DeletePrompt( _( 'Enrollment Code' ) ) )
	{
		DBQuery("DELETE FROM STUDENT_ENROLLMENT_CODES WHERE ID='" . $_REQUEST['id'] . "'");

		// Unset modfunc & ID.
		$_REQUEST['modfunc'] = false;
		$_SESSION['_REQUEST_vars']['modfunc'] = false;
		$_SESSION['_REQUEST_vars']['id'] = false;
	}
}

// Check we have 1 and only one Rollover default code.
$rollover_default_RET = DBGet( DBQuery( "SELECT ID
	FROM STUDENT_ENROLLMENT_CODES
	WHERE SYEAR='" . UserSyear() . "'
	AND TYPE='Add'
	AND DEFAULT_CODE='Y'" ) );

if ( ! $rollover_default_RET
	|| count( $rollover_default_RET ) !== 1 )
{
	$warning[] = _( 'There must be exactly one Rollover default enrollment code (of type Add).' );
}

// FJ fix SQL bug invalid sort order.
echo ErrorMessage( $error );

echo ErrorMessage( $warning, 'warning' );

if ( $_REQUEST['modfunc']!='remove')
{
	$sql = "SELECT ID,TITLE,SHORT_NAME,TYPE,DEFAULT_CODE,SORT_ORDER FROM STUDENT_ENROLLMENT_CODES WHERE SYEAR='".UserSyear()."' ORDER BY SORT_ORDER,TITLE";
	$QI = DBQuery($sql);
	$codes_RET = DBGet($QI,array('TITLE' => 'makeTextInput','SHORT_NAME' => 'makeTextInput','TYPE' => 'makeSelectInput','DEFAULT_CODE' => 'makeCheckBoxInput','SORT_ORDER' => 'makeTextInput'));

	$columns = array('TITLE' => _('Title'),'SHORT_NAME' => _('Short Name'),'TYPE' => _('Type'),'DEFAULT_CODE' => _('Rollover Default'),'SORT_ORDER' => _('Sort Order'));
	$link['add']['html'] = array('TITLE'=>makeTextInput('','TITLE'),'SHORT_NAME'=>makeTextInput('','SHORT_NAME'),'TYPE'=>makeSelectInput('','TYPE'),'DEFAULT_CODE'=>makeCheckBoxInput('','DEFAULT_CODE'),'SORT_ORDER'=>makeTextInput('','SORT_ORDER'));
	$link['remove']['link'] = 'Modules.php?modname='.$_REQUEST['modname'].'&modfunc=remove';
	$link['remove']['variables'] = array('id' => _('ID'));

	echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=update" method="POST">';
	DrawHeader('',SubmitButton(_('Save')));

	ListOutput($codes_RET,$columns,'Enrollment Code','Enrollment Codes',$link);
	echo '<div class="center">' . SubmitButton( _( 'Save' ) ) . '</div>';
	echo '</form>';
}

function makeTextInput($value,$name)
{	global $THIS_RET;

	if ( $THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	if ( $name=='SHORT_NAME')
		$extra = 'size=5 maxlength=10';
	elseif ( $name=='SORT_ORDER')
		$extra = 'size=5 maxlength=10';

	return TextInput($value,'values['.$id.']['.$name.']','',$extra);
}

function makeSelectInput($value,$name)
{	global $THIS_RET;

	if ( $THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	if ( $name=='TYPE')
		$options = array('Add' => _('Add'),'Drop' => _('Drop'));

	return SelectInput($value,'values['.$id.']['.$name.']','',$options);
}

function makeCheckBoxInput($value,$name)
{	global $THIS_RET;

	if ( $THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

//FJ css WPadmin
//	return CheckboxInput($value,'values['.$id.']['.$name.']','','',($id=='new'));
	return CheckboxInput($value, 'values['.$id.']['.$name.']', '', '', ($id=='new'), button('check'), button('x'));
}
