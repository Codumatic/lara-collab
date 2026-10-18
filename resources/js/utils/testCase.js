export const testCaseStatusColors = {
  not_run: 'gray',
  passed: 'green',
  failed: 'red',
  blocked: 'orange',
};

export const testCaseCode = testCase => `TC-${testCase.number}`;

/**
 * Story options grouped under their epic, for a grouped Select.
 */
export const storyOptions = epics =>
  epics
    .filter(epic => epic.stories.length > 0)
    .map(epic => ({
      group: epic.title,
      items: epic.stories.map(story => ({ value: story.id.toString(), label: story.title })),
    }));
