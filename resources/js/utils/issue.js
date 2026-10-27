export const issueStatusColors = {
  new: 'blue',
  in_progress: 'yellow',
  resolved: 'teal',
  deployed: 'violet',
  closed: 'gray',
};

export const issueSeverityColors = {
  critical: 'red',
  major: 'orange',
  medium: 'yellow',
  low: 'gray',
};

export const issuePriorityColors = {
  urgent: 'red',
  high: 'orange',
  medium: 'yellow',
  low: 'blue',
  lowest: 'gray',
};

export const issueCode = issue => `ISS-${issue.number}`;

export const optionLabel = (options, value) =>
  options.find(option => option.value === value)?.label ?? value;
