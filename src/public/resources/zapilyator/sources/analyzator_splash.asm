createAnalSplash
		ld hl,#5800
		ld ix, ANALYS_DATA
		ld bc, #0300
cASc	ld a, (hl) 
		cp #7f : jr c, 1f
		and #7f : ld (hl), a 
		ld (ix + 0), l
		ld (ix + 1), h
		inc ix : inc ix
1		inc hl
		dec bc : ld a, b : or c : jr nz, cASc
			
		xor a
		ld (ix + 0), a
		ld (ix + 1), a
		ret
		
doAnalSplash	
		ld bc,#fffd : ld a, _analyzator_splash : out (c),a : in a,(c)
CUR_AYS	ld e,#00
		cp e : jr nc, $+4
		ld a,e : dec a
		ld (CUR_AYS + 1), a		
		cp _analyzator_splash_sens : jr c, 1f
		
		; or #40
		ld hl, danCS
		ld (hl), #F6
		inc hl : ld (hl), #40
		jr 2f
		
		; and #3f
1		ld hl, danCS
		ld (hl), #E6
		inc hl : ld (hl), #3f
		
2		ld ix, ANALYS_DATA
danS	ld l, (ix + 0)
		ld h, (ix + 1)
		ld a, (hl) 
danCS	or #40 : ld (hl), a
		inc ix : inc ix
		ld a, (ix + 1) : or a : ret z
		jr danS
ANALYS_DATA
