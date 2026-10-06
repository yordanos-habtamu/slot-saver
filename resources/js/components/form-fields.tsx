import type {
    InputHTMLAttributes,
    ReactNode,
    TextareaHTMLAttributes,
} from 'react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type FieldShellProps = {
    id: string;
    label: string;
    hint?: string;
    error?: string;
    children: ReactNode;
};

function FieldShell({ id, label, hint, error, children }: FieldShellProps) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            {children}
            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
            <InputError className="mt-1" message={error} />
        </div>
    );
}

export function TextField({
    id,
    label,
    hint,
    error,
    value,
    onChange,
    ...inputProps
}: Omit<InputHTMLAttributes<HTMLInputElement>, 'id' | 'value' | 'onChange'> & {
    id: string;
    label: string;
    hint?: string;
    error?: string;
    value: string;
    onChange: (value: string) => void;
}) {
    return (
        <FieldShell id={id} label={label} hint={hint} error={error}>
            <Input
                id={id}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="mt-1 block w-full"
                {...inputProps}
            />
        </FieldShell>
    );
}

export function TextAreaField({
    id,
    label,
    hint,
    error,
    value,
    onChange,
    ...textareaProps
}: Omit<
    TextareaHTMLAttributes<HTMLTextAreaElement>,
    'id' | 'value' | 'onChange'
> & {
    id: string;
    label: string;
    hint?: string;
    error?: string;
    value: string;
    onChange: (value: string) => void;
}) {
    return (
        <FieldShell id={id} label={label} hint={hint} error={error}>
            <textarea
                id={id}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="mt-1 block w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                {...textareaProps}
            />
        </FieldShell>
    );
}

export type SelectOption = {
    value: string;
    label: string;
    description?: string;
};

export function SelectField({
    id,
    label,
    hint,
    error,
    value,
    onChange,
    placeholder,
    options,
}: {
    id: string;
    label: string;
    hint?: string;
    error?: string;
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    options: SelectOption[];
}) {
    return (
        <FieldShell id={id} label={label} hint={hint} error={error}>
            <Select value={value} onValueChange={onChange}>
                <SelectTrigger id={id} className="mt-1 w-full">
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </FieldShell>
    );
}
