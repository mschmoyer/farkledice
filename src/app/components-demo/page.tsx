'use client';

import { PlayerCard, GameCard } from '@/components/game';

export default function ComponentsDemoPage() {
  return (
    <div style={{ padding: '20px', backgroundColor: '#1a1a2e', minHeight: '100vh' }}>
      <div style={{ maxWidth: '800px', margin: '0 auto' }}>
        <h1 style={{ color: 'white', marginBottom: '2rem' }}>Farkle Components Demo</h1>

        <h2 style={{ color: 'white', marginTop: '2rem', marginBottom: '1rem' }}>PlayerCard Component</h2>

        <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
          <PlayerCard
            username="FarkleKing42"
            level={42}
            title="the Veteran"
            achievementScore={12500}
            xp={850}
            xpToLevel={1000}
            cardColor="rgba(0, 100, 200, 0.7)"
          />

          <PlayerCard
            username="DiceRoller99"
            level={15}
            title="the Novice"
            achievementScore={2450}
            xp={380}
            xpToLevel={500}
            cardColor="purple"
          />

          <PlayerCard
            username="LuckyPlayer"
            level={100}
            title="the Legendary"
            achievementScore={125680}
            xp={8500}
            xpToLevel={10000}
            picture="https://i.pravatar.cc/150?img=3"
            cardColor="darkblue"
          />

          <PlayerCard
            username="NewPlayer"
            level={1}
            title="the Rookie"
            achievementScore={0}
            xp={50}
            xpToLevel={100}
            cardColor="#2a5"
          />
        </div>

        <h2 style={{ color: 'white', marginTop: '3rem', marginBottom: '1rem' }}>GameCard Component</h2>

        <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
          <GameCard
            gameId="1"
            opponents={['Alice', 'Bob']}
            status="your-turn"
            mode="10-round"
            statusMessage="Your Turn - Round 3"
            onClick={() => console.log('Game clicked')}
          />

          <GameCard
            gameId="2"
            opponents={['Charlie']}
            status="not-started"
            mode="standard"
            onClick={() => console.log('Game clicked')}
          />

          <GameCard
            gameId="3"
            opponents={['David', 'Eve', 'Frank']}
            status="waiting"
            mode="10-round"
            currentPlayer="David"
            onClick={() => console.log('Game clicked')}
          />

          <GameCard
            gameId="4"
            opponents={['Grace', 'Heidi', 'Ivan', 'Judy', 'Karl']}
            status="finished"
            mode="standard"
            onClick={() => console.log('Game clicked')}
          />

          <GameCard
            gameId="5"
            opponents={['Mike', 'Nancy']}
            status="waiting"
            mode="10-round"
            statusMessage="Waiting on others..."
            onClick={() => console.log('Game clicked')}
          />
        </div>
      </div>
    </div>
  );
}
