import { issueCode, issuePriorityColors, issueSeverityColors, optionLabel } from '@/utils/issue';
import { getInitials } from '@/utils/user';
import { DragDropContext, Draggable, Droppable } from '@hello-pangea/dnd';
import {
  ActionIcon,
  Avatar,
  Flex,
  Group,
  Text,
  Tooltip,
  rem,
  useComputedColorScheme,
} from '@mantine/core';
import { IconPlus } from '@tabler/icons-react';
import axios from 'axios';
import { useEffect, useState } from 'react';
import boardClasses from '../Tasks/css/Index.module.css';
import cardClasses from '../Tasks/Index/Task/css/TaskCard.module.css';
import columnClasses from '../Tasks/Index/css/TaskGroup.module.css';

const groupByStatus = (issues, statuses) =>
  Object.fromEntries(
    statuses.map(status => [status.value, issues.filter(issue => issue.status === status.value)])
  );

/**
 * Kanban board with one column per status, styled like the tasks board.
 * Dragging a card to another column saves its new status.
 */
export default function IssuesBoard({ issues, dropdowns, onCardClick, onAddIssue }) {
  const [columns, setColumns] = useState(() => groupByStatus(issues, dropdowns.statuses));
  const computedColorScheme = useComputedColorScheme();

  useEffect(() => {
    setColumns(groupByStatus(issues, dropdowns.statuses));
  }, [issues]);

  const canMove = can('edit issue');

  const onDragEnd = ({ source, destination }) => {
    if (!destination || source.droppableId === destination.droppableId) {
      return;
    }

    const previousColumns = columns;
    const fromStatus = source.droppableId;
    const toStatus = destination.droppableId;
    const movedIssue = { ...columns[fromStatus][source.index], status: toStatus };

    const fromColumn = [...columns[fromStatus]];
    fromColumn.splice(source.index, 1);
    const toColumn = [...columns[toStatus]];
    toColumn.splice(destination.index, 0, movedIssue);

    setColumns({ ...columns, [fromStatus]: fromColumn, [toStatus]: toColumn });

    axios
      .put(route('projects.issues.status', [movedIssue.project_id, movedIssue.id]), {
        status: toStatus,
      })
      .catch(() => {
        setColumns(previousColumns);
        alert('Failed to change the issue status');
      });
  };

  return (
    <div className='kanban-view'>
      <DragDropContext onDragEnd={onDragEnd}>
        <div className={boardClasses.viewport}>
          {dropdowns.statuses.map(status => (
            <div
              key={status.value}
              className={columnClasses.row}
            >
              <div className={columnClasses.group}>
                <Group gap='xs'>
                  <Text
                    size='xl'
                    fw={700}
                  >
                    {status.label}
                  </Text>
                  <Text
                    size='sm'
                    c='dimmed'
                  >
                    {columns[status.value]?.length ?? 0}
                  </Text>
                </Group>
                {can('create issue') && (
                  <Tooltip
                    label='Add issue'
                    openDelay={1000}
                    withArrow
                  >
                    <ActionIcon
                      variant='filled'
                      size='md'
                      radius='xl'
                      onClick={() => onAddIssue(status.value)}
                    >
                      <IconPlus
                        style={{ width: rem(18), height: rem(18) }}
                        stroke={2}
                      />
                    </ActionIcon>
                  </Tooltip>
                )}
              </div>

              <Droppable droppableId={status.value}>
                {(provided, snapshot) => (
                  <Flex
                    direction='column'
                    gap='3px'
                    ref={provided.innerRef}
                    {...provided.droppableProps}
                    className={snapshot.isDraggingOver ? 'isDraggingOver' : ''}
                  >
                    {(columns[status.value] ?? []).map((issue, index) => (
                      <Draggable
                        key={issue.id}
                        draggableId={`issue-${issue.id}`}
                        index={index}
                        isDragDisabled={!canMove}
                      >
                        {draggable => (
                          <div
                            ref={draggable.innerRef}
                            {...draggable.draggableProps}
                            {...draggable.dragHandleProps}
                            className={cardClasses.task}
                          >
                            {issue.priority && (
                              <Tooltip
                                label={`${optionLabel(dropdowns.priorities, issue.priority)} priority`}
                                withArrow
                                openDelay={300}
                              >
                                <div
                                  className={cardClasses.priorityIndicator}
                                  style={{
                                    backgroundColor: `var(--mantine-color-${issuePriorityColors[issue.priority]}-6)`,
                                  }}
                                />
                              </Tooltip>
                            )}

                            <Text
                              className={cardClasses.name}
                              size='xs'
                              fw={500}
                              onClick={() => onCardClick(issue)}
                            >
                              #{issueCode(issue)}: {issue.title}
                            </Text>

                            <Group
                              wrap='nowrap'
                              justify='space-between'
                              mt={5}
                            >
                              {issue.severity ? (
                                <Text
                                  fz={9}
                                  tt='uppercase'
                                  fw={600}
                                  c={`${issueSeverityColors[issue.severity]}.7`}
                                >
                                  {optionLabel(dropdowns.severities, issue.severity)}
                                </Text>
                              ) : (
                                <span />
                              )}

                              {issue.assigned_to_user && (
                                <Tooltip
                                  label={issue.assigned_to_user.name}
                                  openDelay={1000}
                                  withArrow
                                >
                                  <Avatar
                                    src={issue.assigned_to_user.avatar}
                                    radius='xl'
                                    size={20}
                                    color={computedColorScheme === 'light' ? 'white' : 'blue'}
                                  >
                                    {getInitials(issue.assigned_to_user.name)}
                                  </Avatar>
                                </Tooltip>
                              )}
                            </Group>
                          </div>
                        )}
                      </Draggable>
                    ))}
                    <div className={columnClasses.placeholder}>{provided.placeholder}</div>
                  </Flex>
                )}
              </Droppable>
            </div>
          ))}
        </div>
      </DragDropContext>
    </div>
  );
}
