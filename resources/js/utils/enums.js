export const PricingType = {
  HOURLY: 'hourly',
  FIXED: 'fixed',
};

export const TaskStatus = {
  NEW: 'new',
  IN_PROGRESS: 'in_progress',
  RESOLVED: 'resolved',
  DEPLOYED: 'deployed',
  CLOSED: 'closed',
};

export const taskStatusOptions = [
  { value: TaskStatus.NEW, label: 'New' },
  { value: TaskStatus.IN_PROGRESS, label: 'In progress' },
  { value: TaskStatus.RESOLVED, label: 'Resolved' },
  { value: TaskStatus.DEPLOYED, label: 'Deployed' },
  { value: TaskStatus.CLOSED, label: 'Closed' },
];

export const taskStatusLabel = value =>
  taskStatusOptions.find(option => option.value === value)?.label ?? value;

export const Severity = {
  CRITICAL: 'critical',
  MAJOR: 'major',
  MEDIUM: 'medium',
  LOW: 'low',
};

export const severityOptions = [
  { value: Severity.CRITICAL, label: 'Critical', color: '#C92A2A' },
  { value: Severity.MAJOR, label: 'Major', color: '#E8590C' },
  { value: Severity.MEDIUM, label: 'Medium', color: '#F59F00' },
  { value: Severity.LOW, label: 'Low', color: '#868E96' },
];
