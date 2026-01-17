'use client';

import { useState } from 'react';
import {
  Paper,
  TextInput,
  PasswordInput,
  Button,
  Title,
  Text,
  Divider,
  Group,
  Stack,
  Anchor,
  Center,
  Box,
} from '@mantine/core';
import { useForm } from '@mantine/form';
import { IconBrandGoogle, IconBrandFacebook, IconDice } from '@tabler/icons-react';
import classes from './CustomLoginForm.module.css';

export function CustomLoginForm() {
  const [isSignUp, setIsSignUp] = useState(false);
  const [isDevLogin, setIsDevLogin] = useState(false);
  const isDevelopment = process.env.NODE_ENV === 'development';

  const form = useForm({
    initialValues: {
      email: '',
      password: '',
      username: '',
    },
    validate: {
      email: (val) => (/^\S+@\S+$/.test(val) ? null : 'Invalid email'),
      password: (val) => (isDevLogin || val.length >= 8 ? null : 'Password must be at least 8 characters'),
    },
  });

  const handleDevLogin = async (values: typeof form.values) => {
    try {
      const response = await fetch('/api/dev-auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          email: values.email,
          username: values.username || values.email.split('@')[0],
        }),
      });

      const data = await response.json();

      if (response.ok && data.success) {
        window.location.href = data.redirectTo;
      } else {
        alert(data.error || 'Dev login failed');
      }
    } catch (error) {
      console.error('Dev login error:', error);
      alert('Dev login failed');
    }
  };

  const handleSubmit = async (values: typeof form.values) => {
    if (isDevLogin) {
      return handleDevLogin(values);
    }

    // Redirect to Auth0 login with screen hint and login hint
    const params = new URLSearchParams({
      screen_hint: isSignUp ? 'signup' : 'login',
      login_hint: values.email,
      returnTo: '/lobby',
    });
    window.location.href = `/auth/login?${params.toString()}`;
  };

  const handleSocialLogin = (connection: string) => {
    // Redirect to Auth0 login with specific connection
    const params = new URLSearchParams({
      connection,
      returnTo: '/lobby',
    });
    window.location.href = `/auth/login?${params.toString()}`;
  };

  return (
    <Box className={classes.wrapper}>
      <Paper className={classes.form} radius="lg" p={40} withBorder>
        <Center mb="xl">
          <IconDice size={48} className={classes.logo} />
        </Center>

        <Title order={2} className={classes.title} ta="center" mb="xs">
          {isSignUp ? 'Join Farkle Ten' : 'Welcome Back'}
        </Title>

        <Text c="dimmed" size="sm" ta="center" mb="xl">
          {isDevLogin
            ? '🔧 Dev Mode - No password required'
            : isSignUp
            ? 'Create an account to start rolling'
            : 'Sign in to continue your games'}
        </Text>

        {isDevelopment && (
          <Button
            variant="light"
            color="orange"
            fullWidth
            mb="md"
            onClick={() => setIsDevLogin(!isDevLogin)}
          >
            {isDevLogin ? '← Back to Auth0 Login' : '🔧 Dev Login (Local Only)'}
          </Button>
        )}

        {!isDevLogin && (
          <>
            <Stack gap="md" mb="md">
              <Button
                leftSection={<IconBrandGoogle size={20} />}
                variant="default"
                size="md"
                onClick={() => handleSocialLogin('google-oauth2')}
              >
                Continue with Google
              </Button>

              <Button
                leftSection={<IconBrandFacebook size={20} />}
                variant="default"
                size="md"
                onClick={() => handleSocialLogin('facebook')}
              >
                Continue with Facebook
              </Button>
            </Stack>

            <Divider label="Or continue with email" labelPosition="center" my="lg" />
          </>
        )}

        <form onSubmit={form.onSubmit(handleSubmit)}>
          <Stack gap="md">
            {(isSignUp || isDevLogin) && (
              <TextInput
                label="Username"
                placeholder="DiceMaster2026"
                {...form.getInputProps('username')}
              />
            )}

            <TextInput
              label="Email"
              placeholder="you@example.com"
              {...form.getInputProps('email')}
            />

            {!isDevLogin && (
              <>
                <PasswordInput
                  label="Password"
                  placeholder="Your password"
                  {...form.getInputProps('password')}
                />

                {!isSignUp && (
                  <Anchor
                    component="a"
                    href="/auth/login?screen_hint=reset-password"
                    c="dimmed"
                    size="xs"
                  >
                    Forgot password?
                  </Anchor>
                )}
              </>
            )}

            <Button type="submit" fullWidth size="md" className={classes.submitButton}>
              {isDevLogin ? '🔧 Dev Login' : isSignUp ? 'Create Account' : 'Sign In'}
            </Button>
          </Stack>
        </form>

        {!isDevLogin && (
          <Text ta="center" mt="md" size="sm">
            {isSignUp ? 'Already have an account? ' : "Don't have an account? "}
            <Anchor component="button" onClick={() => setIsSignUp(!isSignUp)}>
              {isSignUp ? 'Sign in' : 'Sign up'}
            </Anchor>
          </Text>
        )}
      </Paper>
    </Box>
  );
}
